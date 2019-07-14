<?php

namespace App\Dto;

class UriScanResult
{
    public $domain;
    public $uri;
    public $response;
    public $protocol = 'http';
    public $excerpt;
    public $success = false;


    public function getFullURL(){
        return sprintf('%s://%s%s', $this->protocol, $this->domain, $this->uri);
    }
}