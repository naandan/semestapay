<?php

class Semestapay_Helper {

    public static function generate_signature($merchantCode, $merchantKey, $timestamp) {
        $data_to_hash = $merchantCode . $timestamp . $merchantKey;
        $signature = hash('sha256', $data_to_hash);
        return $signature;
    }
    
    public static function debug($data) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}