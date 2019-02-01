<?php

namespace Payop;

/**
 * ResponseHeaderBag is a container for Response HTTP headers.
 */
class ResponseHeaderBag extends ParameterBag
{
    const DISPOSITION_ATTACHMENT = 'attachment';
    const DISPOSITION_INLINE = 'inline';

    protected $headerNames = [];

    public function __construct(array $headers = [])
    {
        parent::__construct($headers);

        /* RFC2616 - 14.18 says all Responses need to have a Date */
        if (!$this->has('date')) {
            $this->initDate();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $headers = [])
    {
        $this->headerNames = [];

        parent::replace($headers);

        if (!$this->has('date')) {
            $this->initDate();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $values, $replace = true)
    {
        $uniqueKey = \str_replace('_', '-', \strtolower($key));
        $this->headerNames[$uniqueKey] = $key;

        parent::set($key, $values);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $uniqueKey = \str_replace('_', '-', \strtolower($key));
        unset($this->headerNames[$uniqueKey]);

        parent::remove($key);

        if ('date' === $uniqueKey) {
            $this->initDate();
        }
    }

    private function initDate()
    {
        $now = \DateTime::createFromFormat('U', \time());
        $now->setTimezone(new \DateTimeZone('UTC'));
        $this->set('Date', $now->format('D, d M Y H:i:s').' GMT');
    }

    /**
     * Returns the headers as a string.
     *
     * @return string The headers
     */
    public function __toString()
    {
        if (!$headers = $this->all()) {
            return '';
        }

        \ksort($headers);
        $max = \max(\array_map('strlen', \array_keys($headers))) + 1;
        $content = '';
        foreach ($headers as $name => $values) {
            $name = \ucwords($name, '-');
            foreach ($values as $value) {
                $content .= \sprintf("%-{$max}s %s\r\n", $name.':', $value);
            }
        }

        return $content;
    }
}
