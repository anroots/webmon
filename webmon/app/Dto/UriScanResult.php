<?php

namespace App\Dto;

use GuzzleHttp\Psr7\Response;

class UriScanResult
{
    public $domain;
    public $uri;

    /**
     * @var Response
     */
    public $response;
    public $protocol = 'http';
    public $excerpt;
    public $success = false;


    public function getFullURL(){
        return sprintf('%s://%s%s', $this->protocol, $this->domain, $this->uri);
    }
}