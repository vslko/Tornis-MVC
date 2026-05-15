<?php
class ErrorModel extends AbstractModel {

    protected $TABLE       = "errors";

    protected $PRIMARY_KEY = 'id';

    protected $FIELDS = array(
        'id'            => "int",
        'created'       => "date",
        'state'         => "str",
        'message'       => "str",
        'variables'     => "str",
    );

}
