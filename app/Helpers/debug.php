<?php

function f($var, $bool = false)
{
    if($bool){
        file_put_contents(__DIR__.'/../../storage/m.log', var_export($var, true)."\r\n", FILE_APPEND);
    }else{
        file_put_contents(__DIR__.'/../../storage/m.log', var_export($var, true)."\r\n");
    }
}