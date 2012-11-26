<?php

namespace mindplay\easyxml;

use RuntimeException;

/**
 * XML Reader
 */
class XmlReader extends XmlHandler
{
    /**
     * @var bool if true, diagnostic output will be generated
     */
    public $debug = false;

    /**
     * @var bool if true, enable case-folding (read all element/attribute-names in lower-case)
     */
    public $case_folding = false;

    /**
     * @var bool if true, ignore whitespace between elements
     */
    public $skip_white = true;

    /**
     * @var XmlHandler[] $handlers XmlHandler stack
     */
    protected $handlers;

    /**
     * @param string $path absolute path to XML file
     * @return void
     * @throws RuntimeException if the XML file contains error
     */
    public function parse($path)
    {
        /**
         * @var resource $parser
         */

        $this->handlers = array($this);

        $parser = xml_parser_create();

        // skip whitespace-only values
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, $this->skip_white);

        xml_set_element_handler($parser, array($this, 'onStartElement'), array($this, 'onEndElement'));

        xml_set_character_data_handler($parser, array($this, 'onCharacterData'));

        if (!($fp = fopen($path, "r"))) {
            die("could not open XML input: {$path}");
        }

        while ($data = fread($fp, 4096)) {
            if (xml_parse($parser, $data, feof($fp)) === false) {
                throw new RuntimeException(
                    sprintf(
                        "XML error: %s at line %d in %s",
                        xml_error_string(xml_get_error_code($parser)),
                        xml_get_current_line_number($parser),
                        $path
                    )
                );
            }
        }

        xml_parser_free($parser);
    }

    /**
     * @param resource $h
     * @param string $name
     * @param string[] $attr
     *
     * @see parse()
     * @see xml_set_element_handler()
     */
    protected function onStartElement($h, $name, $attr)
    {
        // Apply case folding:

        if ($this->case_folding === true) {
            $name = strtolower($name);

            if (count($attr)) {
                $attr = array_combine(
                    array_map('strtolower', array_keys($attr)),
                    array_values($attr)
                );
            }
        }

        // Diagnostic output:

        if ($this->debug === true) {
            echo "<pre>&lt;$name";

            foreach ($attr as $attrname => $value) {
                echo " $attrname=\"$value\"";
            }

            echo "&gt;</pre>";
        }

        // Notify current handler and push the next handler onto the stack:

        $handler = $this->handlers[ count($this->handlers)-1 ];

        $this->handlers[] = ($handler === null)
            ? null
            : $handler->startElement($name, $attr);
    }

    /**
     * @param resource $h
     * @param string $name
     *
     * @see parse()
     * @see xml_set_element_handler()
     */
    protected function onEndElement($h, $name)
    {
        // Apply case folding:

        if ($this->case_folding === true) {
            $name = strtolower($name);
        }

        // Diagnostic output:

        if ($this->debug === true) {
            echo "<pre>&lt;/$name&gt;</pre>";
        }

        // Pop handler from stack and notify:

        $handler = array_pop($this->handlers);

        if ($handler !== null) {
            $handler->endElement($name);
        }
    }

    /**
     * @param resource $h
     * @param string $data
     *
     * @see parse()
     * @see xml_set_character_data_handler()
     */
    protected function onCharacterData($h, $data)
    {
        // Diagnostic output:

        if ($this->debug === true) {
            echo "<pre>" . $data . "</pre>";
        }

        // Notify top-most handler on current stack:

        $handler = $this->handlers[ count($this->handlers)-1 ];

        if ($handler !== null) {
            $handler->characterData($data);
        }
    }
}
