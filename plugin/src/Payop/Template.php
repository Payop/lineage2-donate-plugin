<?php

namespace Payop;

/**
 * Class Template
 * @package Payop
 */
class Template
{
    /**
     * @var string
     */
    public $basePath;

    /**
     * @param string $basePath
     *
     * @return \Payop\Template
     */
    public static function create($basePath)
    {
        return new static($basePath);
    }

    /**
     * Template constructor.
     *
     * @param string $basePath
     */
    private function __construct($basePath)
    {
        $this->basePath = \rtrim($basePath, '/');
    }

    /**
     * Find and attempt to render a template with variables
     *
     * @param string $templateName
     * @param array $variables
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function render($templateName, $variables = [])
    {
        $template = $this->findTemplate($templateName);
        $output = '';
        if ($template) {
            $output = $this->renderTemplate($template, $variables);
        }

        return $output;
    }

    /**
     * @param string $templateName
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    private function findTemplate($templateName)
    {
        $file = "{$this->basePath}/{$templateName}.php";

        if (!\file_exists($file)) {
            throw new \RuntimeException("Template {$file} does not exists");
        }

        return $file;
    }

    /**
     * @param string $template
     * @param array $variables
     *
     * @return false|string
     */
    private function renderTemplate($template, $variables)
    {
        ob_start();
        foreach ($variables as $key => $value) {
            ${$key} = $value;
        }
        include $template;

        return ob_get_clean();
    }
}