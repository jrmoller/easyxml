<?php

use mindplay\easyxml\XmlReader;
use mindplay\easyxml\XmlHandler;

require __DIR__ . '/mindplay/easyxml/ParserException.php';
require __DIR__ . '/mindplay/easyxml/XmlHandler.php';
require __DIR__ . '/mindplay/easyxml/XmlReader.php';

header('Content-type: text/plain');

$SAMPLE = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<cats>
    <cat name="whiskers">
        <kitten name="mittens"/>
    </cat>
    <cat name="tinker">
        <kitten name="binky"/>
    </cat>
    <notes>
        Hello World
    </notes>
</cats>
XML;

class Cats
{
    /** @var Cat[] */
    public $cats = array();

    /** @var string */
    public $notes;
}

class Cat
{
    public $name;

    /** @var Kitten[] */
    public $kittens = array();
}

class Kitten extends Cat
{}

test(
    'Parsing elements and attributes',
    function () use ($SAMPLE) {
        $doc = new XmlReader();

        $model = new Cats();

        $doc['cats'] = function (XmlHandler $cats) use ($model) {
            $cats['cat'] = function (XmlHandler $cat_node, $name) use ($model) {
                $cat = new Cat();
                $cat->name = $name;

                $model->cats[] = $cat;

                $cat_node['kitten'] = function ($name) use ($cat) {
                    $kitten = new Kitten();
                    $kitten->name = $name;

                    $cat->kittens[] = $kitten;
                };
            };

            $cats['notes'] = function (XmlHandler $notes) use ($model) {
                $notes['#text'] = function ($text) use ($model) {
                    $model->notes = $text;
                };
            };
        };

        $doc->parse($SAMPLE);

        eq(count($model->cats), 2, 'document contains 2 cats');

        eq(count($model->cats[0]->kittens), 1, 'first cat has 1 kitten');
        eq($model->cats[0]->name, 'whiskers');
        eq($model->cats[0]->kittens[0]->name, 'mittens');

        eq(count($model->cats[1]->kittens), 1, 'second cat has 1 kitten');
        eq($model->cats[1]->name, 'tinker');
        eq($model->cats[1]->kittens[0]->name, 'binky');

        eq($model->notes, 'Hello World'); # huh?
    }
);

exit(status()); // exits with errorlevel (for CI tools etc.)

// https://gist.github.com/mindplay-dk/4260582

/**
 * @param string   $name     test description
 * @param callable $function test implementation
 */
function test($name, $function)
{
    echo "\n=== $name ===\n\n";

    try {
        call_user_func($function);
    } catch (Exception $e) {
        ok(false, "UNEXPECTED EXCEPTION", $e);
    }
}

/**
 * @param bool   $result result of assertion
 * @param string $why    description of assertion
 * @param mixed  $value  optional value (displays on failure)
 */
function ok($result, $why = null, $value = null)
{
    if ($result === true) {
        echo "- PASS: " . ($why === null ? 'OK' : $why) . ($value === null ? '' : ' (' . format($value) . ')') . "\n";
    } else {
        echo "# FAIL: " . ($why === null ? 'ERROR' : $why) . ($value === null ? '' : ' - ' . format($value, true)) . "\n";
        status(false);
    }
}

/**
 * @param mixed  $value    value
 * @param mixed  $expected expected value
 * @param string $why      description of assertion
 */
function eq($value, $expected, $why = null)
{
    $result = $value === $expected;

    $info = $result
        ? format($value)
        : "expected: " . format($expected, true) . ", got: " . format($value, true);

    ok($result, ($why === null ? $info : "$why ($info)"));
}

/**
 * @param string   $exception_type Exception type name
 * @param string   $why            description of assertion
 * @param callable $function       function expected to throw
 */
function expect($exception_type, $why, $function)
{
    try {
        call_user_func($function);
    } catch (Exception $e) {
        if ($e instanceof $exception_type) {
            ok(true, $why, $e);
            return;
        } else {
            $actual_type = get_class($e);
            ok(false, "$why (expected $exception_type but $actual_type was thrown)");
            return;
        }
    }

    ok(false, "$why (expected exception $exception_type was NOT thrown)");
}

/**
 * @param mixed $value
 * @param bool  $verbose
 *
 * @return string
 */
function format($value, $verbose = false)
{
    if ($value instanceof Exception) {
        return get_class($value)
        . ($verbose ? ": \"" . $value->getMessage() . "\"" : '');
    }

    if (! $verbose && is_array($value)) {
        return 'array[' . count($value) . ']';
    }

    if (is_bool($value)) {
        return $value ? 'TRUE' : 'FALSE';
    }

    if (is_object($value) && !$verbose) {
        return get_class($value);
    }

    return print_r($value, true);
}

/**
 * @param bool|null $status test status
 *
 * @return int number of failures
 */
function status($status = null)
{
    static $failures = 0;

    if ($status === false) {
        $failures += 1;
    }

    return $failures;
}
