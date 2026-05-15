<?php
class ProfileController extends AabstractController {

    public function init( $R ) {
        parent::init($R);
        if ( !Session::logged() ) {
            if ($R->isAjax()) { $R->replyJsonError( Session::locales('errors:auth_expired')); }
            else { $this->show_error_page(Session::locales('errors:auth_expired')); }
        }
    }


    public function do_( $R ) {
        $R->redirect('./settings', true);
    }





    public function doBilling( $R ) {
        VIEW::template("billing");
        VIEW::set(
            'payments',
            $this->getCollection('Payments')->dbLoad(
                "[table].client_id=".Session::get('id')." and [table].provider_state!='no_payment_required' and [table].state in ('done', 'error', 'refund') is not NULL",
                "transaction_date DESC",
                30)->rows()
        );
        VIEW::show(true, $this->EXTRAS);
    }






    public function doSupport( $R ) {
        VIEW::template("support");
        VIEW::show(true, $this->EXTRAS);
    }




    public function doUnite( $R ) {
        $TG = new Telegram($this, Session::get_whitelabel('auth_tg_client'), Session::get_whitelabel('auth_tg_secret') );
        VIEW::set(
            'telegram_button',
            $TG->get_custom_button(
                Session::get_current_locale('id'),
                null,
                Session::locales("unite:connect"),
                "btn t-button small btn-goto w-100"
            )
        );

        VIEW::set('unite_error', null);
        if ( !empty($_SESSION['unite_error']) ) {
            VIEW::set('unite_error', $_SESSION['unite_error']);
            unset($_SESSION['unite_error']);
        }

        VIEW::template("connected_accounts");
        VIEW::show(true, $this->EXTRAS);
    }


    public function doUnite_disconnect( $R ) {
        if (!$R->isAjax()) { $this->show_error_page( Session::locales('errors:bad_request')); }

        $kind = strtoupper( $R->getPost('auth',"unknown", false) );
        $auth = Session::get('auth');
        if ( count($auth) <= 1 ) { $R->replyJsonError( Session::locales('errors:invalid_data')); }

        unset($auth[$kind]);
        $client = $this->getModel('Client');
        $client->set('id', Session::get('id'))
               ->set('auth', json_encode($auth))
               ->save(null,false);
        Session::set('auth', $auth);
        $R->replyJson( array('reload'=>true,), "ok");
    }





    public function doSettings( $R ) {
        VIEW::template("settings");
        VIEW::set('timezones', GLOSSARY::getFullTimeZones());
        VIEW::show(true, $this->EXTRAS);
    }


    public function doSettings_save( $R ) {
        if (!$R->isAjax()) { $this->show_error_page( Session::locales('errors:bad_request')); }

        // check email
        $email = $R->getPost('email', "", false);
        if ( !CHK::check( array('email'=>$email), array('email'=>"email") ) ) { $R->replyJsonError( Session::locales('errors:invalid_email') ); }

        $email_exists = $this->getCollection('Clients')->dbCount("email='".$email."' and id != ".Session::get('id')." and wl_id=".Session::get_whitelabel('id'));
        if ($email_exists>0) { $R->replyJsonError( Session::locales('errors:already_used_email') ); }

        // update in database
        $client = $this->getModel('Client');
        $client->set('id', Session::get('id'))
               ->set('timezone', $R->getPost('timezone', "Europe/London", false))
               ->set('email', $email)
               ->save(null, true);

        // update in session
        Session::set('timezone', $client->get('timezone'));
        Session::set('email', $client->get('email'));

        // reply to frontend
        $R->replyJson(array('reload'=>false), Session::locales('misc:saved') );
    }






    public function doReferral( $R ) {
        if (Session::get_whitelabel('is_native') != 1) { $this->show_error_page( Session::locales('errors:bad_request')); }

        $referral = array(
            'total'     => number_format($this->getCollection('ReferralCommissions')->dbSumm('amount', 'recipient_client_id=' . Session::get('id')), 2, ".", ""),
            'unpaid'    => number_format($this->getCollection('ReferralCommissions')->dbSumm('unpaid', 'recipient_client_id=' . Session::get('id')), 2, ".", ""),
            'count'     => $this->getCollection('Clients')->dbCount('ref_parent_client_id='.Session::get('id')),
            'url_web'   => strtolower($_SERVER["REQUEST_SCHEME"]."://".$_SERVER["HTTP_HOST"]."/".Session::get_current_locale('id')."/?".SITE_REFERRAL."=".Session::get('ref_hash')),
            'url_tg'    => "https://t.me/".Session::get_whitelabel('auth_tg_client')."?start=-".Session::get('ref_hash'),
            'promo_limit'   => (int)SITE_REFERRAL_PROMO_AMOUNT,
            'payout_limit'  => (int)SITE_REFERRAL_PAYOUT_AMOUNT,
            'ref_secondary_rate' => ( Session::get('ref_percent') - SITE_REFERRAL_RATE ),
            'periods'   => array(),
            'report_count'  => "0",
            'report_total'  => "0.00",
        );

        $start    = (new DateTime(Session::get('registered')))->modify('first day of this month');
        $end      = (new DateTime())->modify('first day of next month');
        $interval = DateInterval::createFromDateString('1 month');
        $period   = new DatePeriod($start, $interval, $end);
        foreach ($period as $dt) {
            $referral['periods'][] = array(
                'id'        => $dt->format('Y-m'),
                'name'      => $dt->format("m / Y"),
                'select'    => false
            );
        }
        if ( count($referral['periods']) ) {
            $referral['periods'][count($referral['periods'])-1]['select'] = true;
            $dt    = (new DateTime($referral['periods'][count($referral['periods'])-1]['id'].'-01 12:00:00'));
            $from = $dt->format("Y-m-d H:i:s");
            $dt->modify('first day of next month');

            $condition = "[table].created>='".$from."' AND [table].created<'".$dt->format("Y-m-d H:i:s")."' AND [table].recipient_client_id=".Session::get('id');
            $direct_condition = $condition . " AND [table].leverage='DIRECT'";
            $premium_condition = $condition . " AND [table].leverage='PREMIUM'";
            $referral['report_count'] = $this->getCollection('ReferralCommissions')->dbCount($condition);
            $referral['report_total'] = number_format( (float)$this->getCollection('ReferralCommissions')->dbSumm("amount", $condition), 2, ".", "");
            $referral['report_direct'] = number_format( (float)$this->getCollection('ReferralCommissions')->dbSumm("amount", $direct_condition), 2, ".", "");
            $referral['report_premium'] = number_format( (float)$this->getCollection('ReferralCommissions')->dbSumm("amount", $premium_condition), 2, ".", "");
        }
        VIEW::set('referral',$referral);

        VIEW::set('promocodes', $this->getCollection('ReferralPromocodes')->dbLoad('client_id='.Session::get('id'), "id", null)->rows() );

        VIEW::template("referral");
        VIEW::show(
            true,
            array_merge(
                array(
                    'ref_base_rate'     => SITE_REFERRAL_RATE,
                    'ref_payout_amount' => (int)SITE_REFERRAL_PAYOUT_AMOUNT,
                    'ref_promo_amount'  => (int)SITE_REFERRAL_PROMO_AMOUNT,
                    'ref_premium_rate'  => Session::get('ref_percent'),
                    'ref_secondary_rate'=> $referral['ref_secondary_rate'],
                ),
                $this->EXTRAS
            )
        );
    }


    public function doReferral_report($R) {
        if (!$R->isAjax()) { $this->show_error_page( Session::locales('errors:bad_request')); }

        $period = $R->getPost('period', @date('Y-m'), false);
        try {
            $dt    = (new DateTime($period.'-01 12:00:00'));
            $from = $dt->format("Y-m-d H:i:s");
            $dt->modify('first day of next month');
            $till = $dt->format("Y-m-d H:i:s");
        }
        catch( Exception $e) {
            throw MantellaException('Bad request', 400);
        }

        $condition = "[table].created>='".$from."' AND [table].created<'".$till."' AND [table].recipient_client_id=".Session::get('id');
        $direct_condition = $condition . " AND [table].leverage='DIRECT'";
        $premium_condition = $condition . " AND [table].leverage='PREMIUM'";
        $R->replyJson(
            array(
                'count'            => $this->getCollection('ReferralCommissions')->dbCount($condition),
                'direct_amount'    => number_format( (float)$this->getCollection('ReferralCommissions')->dbSumm("amount", $direct_condition), 2, ".", ""),
                'premium_amount'   => number_format( (float)$this->getCollection('ReferralCommissions')->dbSumm("amount", $premium_condition), 2, ".", ""),
                'amount'           => number_format( (float)$this->getCollection('ReferralCommissions')->dbSumm("amount", $condition), 2, ".", ""),
            ),
            null
        );
    }


    public function doReferral_export($R) {
        $period = $R->getVar('period', @date('Y-m'), false);
        try {
            $dt    = (new DateTime($period.'-01 12:00:00'));
            $from = $dt->format("Y-m-d H:i:s");
            $dt->modify('first day of next month');
            $till = $dt->format("Y-m-d H:i:s");
        }
        catch( Exception $e) { throw MantellaException('Bad request', 400); }

        include_once realpath(M_ROOT_PATH . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "vendors" . DIRECTORY_SEPARATOR . "XLSXWritter/xlsxwriter.light.class.php");
        $xls = new XLSXWriterLight();
        $headers  =  array(
            "Date"      => "string",
            "Amount"    => "string",
            "Rate"      => "string",
            "Commission"=> "string",
            "Paid"      => "string",
        );
        $xls->setAuthor(Session::get_whitelabel('name'));
        $xls->writeSheetHeader('Sheet1', $headers);

        $data = $this->getCollection('ReferralCommissions')->dbLoad("[table].created>='".$from."' AND [table].created<'".$till."' AND [table].recipient_client_id=".Session::get('id'), "created")->rows();
        foreach($data as $i => $d) {
            $row = array(
                $d['created'],
                $d['payment_amount'],
                $d['rate'],
                $d['amount'],
                ($d['processed'] ?: "-"),
            );
            $xls->writeSheetRow('Sheet1', $row);
        }
        header("Expires: 0");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Content-Type: application/octet-stream;");
        header("Content-Transfer-Encoding: binary");
        header('Content-Disposition: attachment; filename="referral_report.xlsx"' );
        ob_clean();
        flush();
        $xls->writeToStdOut();
        exit;
    }


    public function doReferral_premium_request($R) {
        if (!$R->isAjax()) { $this->show_error_page( Session::locales('errors:bad_request')); }
        $data = array(
            'name'      => $R->getPost('name', "", false),
            'email'     => $R->getPost('email', "", false),
            'phone'     => $R->getPost('phone', "", false),
            'comment'   => $R->getPost('comment', "-", false),
            'agree'     => $R->getPost('agree', "off", false),
        );
        $rules = array(
            'name'      => "sizes:2,128",
            'email'     => "email",
            'phone'     => "sizes:7,24",
            'comment'   => "sizes:0,1024",
            'agree'     => "equal:on",
        );
        if ( !CHK::check($data, $rules) ) {
            $fields = array();
            foreach(CHK::getUnvalidated() as $e) { $fields[] = $e['name']; }
            $R->replyJsonError( implode(",",$fields) );
        }

        $mod = $this->getModel('Partner');
        $mod->set('wl_id', Session::get_whitelabel('id') )
            ->set('kind',"PREMIUM")
            ->set('client_id', (Session::logged() ? Session::get('id') : "NULL") )
            ->set('name',    $data['name'])
            ->set('email',   $data['email'])
            ->set('phone',   $data['phone'])
            ->set('message', $data['comment'])
            ->add(false);

        $R->replyJson(array("reload"=>false), "ok");
    }



    public function doReferral_get_unpaid( $R ) {
        if (!$R->isAjax()) { $this->show_error_page( Session::locales('errors:bad_request')); }

        $unpaid = number_format($this->getCollection('ReferralCommissions')->dbSumm('unpaid', 'recipient_client_id=' . Session::get('id')), 2, ".", "");
        $R->replyJson(
            array(
                'amount' => (float)$unpaid,
                'limit'  => (int)SITE_REFERRAL_PROMO_AMOUNT,
            ),
            null
        );
    }


    public function doReferral_generate_promocode( $R ) {
        if (!$R->isAjax()) { $this->show_error_page(LNG::get('site.invalid_request')); }

        $amount = number_format( (float)str_replace(",",".", $R->getPost('amount', "0.00", false)), 2, ".", "");
        $unpaid = $this->getCollection('ReferralCommissions')->dbSumm('unpaid', 'recipient_client_id=' . Session::get('id'));

        if ($amount <= 0 or $amount > $unpaid) { $R->replyJsonError( Session::locales('errors:invalid_amount') ); }

        // calculate commissions
        $commissions = $this->getCollection('ReferralCommissions')->get_active_by_client(Session::get('id'));
        $c_id = null;
        $c_amount = null;
        $c_ids = array();
        $c_total = 0;
        foreach ($commissions as $c) {
            $c_total = $c_total + $c['commission'];
            if ($c_total <= $amount) {
                $c_ids[] = $c['id'];
            }
            elseif ($c_total == $amount) {
                $c_ids[] = $c['id'];
                break;
            }
            else {
                $c_id = $c['id'];
                $c_amount = $c_total-$amount;
                break;
            }
        }

        // add new promocode
        $promo = $this->getModel('Promocode');
        $promo->set('wl_id', Session::get_whitelabel('id') )
            ->set('code', "RF".$promo->genHash(10) )
            ->set('valid_from', @date('Y-m-d').' 00:00:00' )
            ->set('valid_before', @date('Y-m-d', strtotime('+1 year')).' 00:00:00' )
            ->set('amount', $amount)
            ->set('currency_id', Session::get_whitelabel('currency_id') )
            ->set('type', "onetime")
            ->set('usage_limit', "1")
            ->set('is_valid', "Y")
            ->add(true);

        if (!$promo->get('id')) { $R->replyJsonError( Session::locales('errors:fatal') ); }

        // add promo into referral program
        $promo_ref = $this->getModel('ReferralPromocode');
        $promo_ref->set('promocode_id', $promo->get('id'))
                  ->set('client_id', Session::get('id'))
                  ->add(false);

        // topdown from commissions
        $comm = $this->getModel('ReferralCommission');
        foreach($c_ids as $id) {
            $comm->clear(false)
                ->set('id', $id)
                ->set('processed', "NOW")
                ->set('unpaid', "0")
                ->save(null,false);
        }
        if ($c_id) {
            $comm->clear(false)
                ->set('id', $c_id)
                ->set('unpaid', $c_amount)
                ->save(null,false);
        }

        $R->replyJson(
            array(
                'code'   => (string)$promo->get("code"),
                'unpaid' => (float)$this->getCollection('ReferralCommissions')->dbSumm('unpaid', 'recipient_client_id=' . Session::get('id')),
                'limit'  => (int)SITE_REFERRAL_PROMO_AMOUNT,
                'note'   => str_replace(
                    array( "{currency}", "{amount}"),
                    array( Session::get_whitelabel('currency_sign'), $amount),
                    Session::locales('referral:promocode_success_note')
                )
            ),
            null
        );

    }


}