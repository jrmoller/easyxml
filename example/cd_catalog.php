<?php

require dirname(dirname(__FILE__)) . '/mindplay/easyxml/XmlHandler.php';
require dirname(dirname(__FILE__)) . '/mindplay/easyxml/XmlReader.php';

use mindplay\easyxml\XmlReader;
use mindplay\easyxml\XmlHandler;

header('Content-type: text/plain');

// Define a simple model for the file we're going to read:

class Catalog
{
    /**
     * @var CD[]
     */
    public $cds = array();
}

class CD
{
    public $title;
    public $artist;
    public $country;
    public $company;
    public $price;
    public $year;
}

// Create and configure the XML reader:

$model = new Catalog();

$doc = new XmlReader();

$doc->case_folding = true;

$doc['catalog'] = function (XmlHandler $catalog) use ($model) {
    $catalog['cd'] = function (XmlHandler $cd) use ($model) {
        $item = new CD();

        $model->cds[] = $item;

        $cd['title'] = function (XmlHandler $title) use ($item) {
            $title['#text'] = function ($text) use ($item) {
                $item->title = trim($text);
            };
        };

        $cd['artist'] = function (XmlHandler $artist) use ($item) {
            $artist['#text'] = function ($text) use ($item) {
                $item->artist = trim($text);
            };
        };

        $cd['country'] = function (XmlHandler $country) use ($item) {
            $country['#text'] = function ($text) use ($item) {
                $item->country = trim($text);
            };
        };

        $cd['company'] = function (XmlHandler $company) use ($item) {
            $company['#text'] = function ($text) use ($item) {
                $item->company = trim($text);
            };
        };

        $cd['price'] = function (XmlHandler $price) use ($item) {
            $price['#text'] = function ($text) use ($item) {
                $item->price = floatval($text);
            };
        };

        $cd['year'] = function (XmlHandler $year) use ($item) {
            $year['#text'] = function ($text) use ($item) {
                $item->year = intval($text);
            };
        };
    };
};

// Run it:

$path = dirname(__FILE__) . '/cd_catalog.xml';

$doc->parse($path);

// Dump the result:

var_dump($model);
