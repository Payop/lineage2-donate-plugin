<?php

namespace Payop;

/**
 * Request represents an HTTP request.
 */
class Request
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    /**
     * Request body parameters ($_POST).
     *
     * @var \Payop\ParameterBag
     */
    public $request;

    /**
     * Query string parameters ($_GET).
     *
     * @var \Payop\ParameterBag
     */
    public $query;

    /**
     * Server and execution environment parameters ($_SERVER).
     *
     * @var \Payop\ServerBag
     */
    public $server;

    /**
     * Headers (taken from the $_SERVER).
     *
     * @var \Payop\ParameterBag
     */
    public $headers;

    /**
     * @var string|resource|false|null
     */
    private $content;

    /**
     * @var string
     */
    private $pathInfo;

    /**
     * @var string
     */
    private $requestUri;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private static $formats;

    /**
     * @param array $query The GET parameters
     * @param array $request The POST parameters
     * @param array $server The SERVER parameters
     */
    public function __construct(array $query = [], array $request = [], array $server = [])
    {
        $this->initialize($query, $request, $server);
    }


    /**
     * Sets the parameters for this request.
     *
     * @param array $query The GET parameters
     * @param array $request The POST parameters
     * @param array $server The SERVER parameters
     */
    public function initialize(array $query = [], array $request = [], array $server = [])
    {
        $this->request = new ParameterBag($request);
        $this->query = new ParameterBag($query);
        $this->server = new ServerBag($server);
        $this->headers = new ParameterBag($this->server->getHeaders());
    }

    /**
     * Creates a new request with values from PHP's super globals.
     *
     * @return static
     */
    public static function createFromGlobals()
    {
        $request = new static($_GET, $_POST, $_SERVER);

        if (0 === \strpos($request->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded')
            && \in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), ['PUT', 'DELETE', 'PATCH'])
        ) {
            \parse_str($request->getContent(), $data);
            $request->request = new ParameterBag($data);
        }

        return $request;
    }

    /**
     * Returns the request as a string.
     *
     * @return string The request
     */
    public function __toString()
    {
        try {
            $content = $this->getContent();
        } catch (\LogicException $e) {
        }

        return
            \sprintf('%s %s %s', $this->getMethod(), $this->getRequestUri(), $this->server->get('SERVER_PROTOCOL'))."\r\n".
            $content;
    }

    /**
     * Gets a "parameter" value from any bag.
     *
     * @param string $key The key
     * @param mixed $default The default value if the parameter key does not exist
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this !== $result = $this->query->get($key, $this)) {
            return $result;
        }

        if ($this !== $result = $this->request->get($key, $this)) {
            return $result;
        }

        return $default;
    }

    /**
     * Returns current script name.
     *
     * @return string
     */
    public function getScriptName()
    {
        return $this->server->get('SCRIPT_NAME', $this->server->get('ORIG_SCRIPT_NAME', ''));
    }

    /**
     * Returns the path being requested relative to the executed script.
     *
     * The path info always starts with a /.
     *
     * Suppose this request is instantiated from /mysite on localhost:
     *
     *  * http://localhost/mysite              returns an empty string
     *  * http://localhost/mysite/about        returns '/about'
     *  * http://localhost/mysite/enco%20ded   returns '/enco%20ded'
     *  * http://localhost/mysite/about?var=1  returns '/about'
     *
     * @return string The raw path (i.e. not urldecoded)
     */
    public function getPathInfo()
    {
        if (null === $this->pathInfo) {
            $this->pathInfo = $this->preparePathInfo();
        }

        return $this->pathInfo;
    }

    /**
     * Returns the root path from which this request is executed.
     *
     * Suppose that an index.php file instantiates this request object:
     *
     *  * http://localhost/index.php         returns an empty string
     *  * http://localhost/index.php/page    returns an empty string
     *  * http://localhost/web/index.php     returns '/web'
     *  * http://localhost/we%20b/index.php  returns '/we%20b'
     *
     * @return string The raw path (i.e. not urldecoded)
     */
    public function getBasePath()
    {
        if (null === $this->basePath) {
            $this->basePath = $this->prepareBasePath();
        }

        return $this->basePath;
    }

    /**
     * Returns the root URL from which this request is executed.
     *
     * The base URL never ends with a /.
     *
     * This is similar to getBasePath(), except that it also includes the
     * script filename (e.g. index.php) if one exists.
     *
     * @return string The raw URL (i.e. not urldecoded)
     */
    public function getBaseUrl()
    {
        if (null === $this->baseUrl) {
            $this->baseUrl = $this->prepareBaseUrl();
        }

        return $this->baseUrl;
    }

    /**
     * Returns the requested URI (path and query string).
     *
     * @return string The raw URI (i.e. not URI decoded)
     */
    public function getRequestUri()
    {
        if (null === $this->requestUri) {
            $this->requestUri = $this->prepareRequestUri();
        }

        return $this->requestUri;
    }

    /**
     * Sets the request method.
     *
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = null;
        $this->server->set('REQUEST_METHOD', $method);
    }

    /**
     * Gets the request "intended" method.
     * The method is always an uppercased string.
     *
     * @return string The request method
     *
     * @see getRealMethod()
     */
    public function getMethod()
    {
        if (null === $this->method) {
            $this->method = \strtoupper($this->server->get('REQUEST_METHOD', 'GET'));
        }

        return $this->method;
    }

    /**
     * Checks if the request method is of specified type.
     *
     * @param string $method Uppercase request method (GET, POST etc)
     *
     * @return bool
     */
    public function isMethod($method)
    {
        return $this->getMethod() === \strtoupper($method);
    }

    /**
     * Returns the request body content.
     *
     * @param bool $asResource If true, a resource will be returned
     *
     * @return string|resource The request body content or a resource to read the body stream
     *
     * @throws \LogicException
     */
    public function getContent($asResource = false)
    {
        $currentContentIsResource = \is_resource($this->content);

        if (true === $asResource) {
            if ($currentContentIsResource) {
                \rewind($this->content);

                return $this->content;
            }

            // Content passed in parameter (test)
            if (\is_string($this->content)) {
                $resource = \fopen('php://temp', 'r+');
                \fwrite($resource, $this->content);
                \rewind($resource);

                return $resource;
            }

            $this->content = false;

            return \fopen('php://input', 'rb');
        }

        if ($currentContentIsResource) {
            \rewind($this->content);

            return \stream_get_contents($this->content);
        }

        if (null === $this->content || false === $this->content) {
            $this->content = \file_get_contents('php://input');
        }

        return $this->content;
    }

    /**
     * Returns true if the request is a XMLHttpRequest.
     *
     * It works if your JavaScript library sets an X-Requested-With HTTP header.
     * It is known to work with common JavaScript frameworks:
     *
     * @see http://en.wikipedia.org/wiki/List_of_Ajax_frameworks#JavaScript
     *
     * @return bool true if the request is an XMLHttpRequest, false otherwise
     */
    public function isXmlHttpRequest()
    {
        return 'XMLHttpRequest' === $this->headers->get('X-Requested-With');
    }

    /*
     * The following methods are derived from code of the Zend Framework (1.10dev - 2010-01-24)
     *
     * Code subject to the new BSD license (http://framework.zend.com/license/new-bsd).
     *
     * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
     */

    /**
     * Gets the mime type associated with the format.
     *
     * @param string $format The format
     *
     * @return string|null The associated mime type (null if not found)
     */
    public function getMimeType($format)
    {
        if (null === static::$formats) {
            static::initializeFormats();
        }

        return isset(static::$formats[$format]) ? static::$formats[$format][0] : null;
    }

    /**
     * Gets the mime types associated with the format.
     *
     * @param string $format The format
     *
     * @return array The associated mime types
     */
    public static function getMimeTypes($format)
    {
        if (null === static::$formats) {
            static::initializeFormats();
        }

        return isset(static::$formats[$format]) ? static::$formats[$format] : [];
    }

    /**
     * Gets the request's scheme.
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    /**
     * Checks whether the request is secure or not.
     *
     * This method can read the client protocol from the "X-Forwarded-Proto" header
     * when trusted proxies were set via "setTrustedProxies()".
     *
     * The "X-Forwarded-Proto" header must contain the protocol: "https" or "http".
     *
     * @return bool
     */
    public function isSecure()
    {
        $https = $this->server->get('HTTPS');

        return !empty($https) && 'off' !== \strtolower($https);
    }

    /**
     * Gets the scheme and HTTP host.
     *
     * If the URL was called with basic authentication, the user
     * and the password are not added to the generated string.
     *
     * @return string The scheme and HTTP host
     */
    public function getSchemeAndHttpHost()
    {
        return $this->getScheme().'://'.$this->getHttpHost();
    }

    /**
     * Returns the host name.
     *
     * @return string
     */
    public function getHost()
    {
        if (!$host = $this->headers->get('HOST')) {
            if (!$host = $this->server->get('SERVER_NAME')) {
                $host = $this->server->get('SERVER_ADDR', '');
            }
        }

        // trim and remove port number from host
        // host is lowercase as per RFC 952/2181
        $host = \strtolower(\preg_replace('/:\d+$/', '', \trim($host)));

        return $host;
    }

    /**
     * Returns the HTTP host being requested.
     *
     * The port name will be appended to the host if it's non-standard.
     *
     * @return string
     */
    public function getHttpHost()
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();

        if (('http' === $scheme && 80 == $port) || ('https' === $scheme && 443 == $port)) {
            return $this->getHost();
        }

        return $this->getHost().':'.$port;
    }
    /**
     * Returns the port on which the request is made.
     *
     * This method can read the client port from the "X-Forwarded-Port" header
     * when trusted proxies were set via "setTrustedProxies()".
     *
     * The "X-Forwarded-Port" header must contain the client port.
     *
     * @return int|string can be a string if fetched from the server bag
     */
    public function getPort()
    {
        if (!$host = $this->headers->get('HOST')) {
            return $this->server->get('SERVER_PORT');
        }

        if ('[' === $host[0]) {
            $pos = \strpos($host, ':', \strrpos($host, ']'));
        } else {
            $pos = \strrpos($host, ':');
        }

        if (false !== $pos) {
            return (int) \substr($host, $pos + 1);
        }

        return 'https' === $this->getScheme() ? 443 : 80;
    }

    protected function prepareRequestUri()
    {
        $requestUri = '';

        if ('1' == $this->server->get('IIS_WasUrlRewritten') && '' != $this->server->get('UNENCODED_URL')) {
            // IIS7 with URL Rewrite: make sure we get the unencoded URL (double slash problem)
            $requestUri = $this->server->get('UNENCODED_URL');
            $this->server->remove('UNENCODED_URL');
            $this->server->remove('IIS_WasUrlRewritten');
        } elseif ($this->server->has('REQUEST_URI')) {
            $requestUri = $this->server->get('REQUEST_URI');

            // HTTP proxy reqs setup request URI with scheme and host [and port] + the URL path, only use URL path
            $uriComponents = \parse_url($requestUri);

            if (isset($uriComponents['path'])) {
                $requestUri = $uriComponents['path'];
            }

            if (isset($uriComponents['query'])) {
                $requestUri .= '?'.$uriComponents['query'];
            }
        } elseif ($this->server->has('ORIG_PATH_INFO')) {
            // IIS 5.0, PHP as CGI
            $requestUri = $this->server->get('ORIG_PATH_INFO');
            if ('' != $this->server->get('QUERY_STRING')) {
                $requestUri .= '?'.$this->server->get('QUERY_STRING');
            }
            $this->server->remove('ORIG_PATH_INFO');
        }

        // normalize the request URI to ease creating sub-requests from this request
        $this->server->set('REQUEST_URI', $requestUri);

        return $requestUri;
    }

    /**
     * Prepares the base URL.
     *
     * @return string
     */
    protected function prepareBaseUrl()
    {
        $filename = \basename($this->server->get('SCRIPT_FILENAME'));

        if (\basename($this->server->get('SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->server->get('SCRIPT_NAME');
        } elseif (\basename($this->server->get('PHP_SELF')) === $filename) {
            $baseUrl = $this->server->get('PHP_SELF');
        } elseif (\basename($this->server->get('ORIG_SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->server->get('ORIG_SCRIPT_NAME'); // 1and1 shared hosting compatibility
        } else {
            // Backtrack up the script_filename to find the portion matching
            // php_self
            $path = $this->server->get('PHP_SELF', '');
            $file = $this->server->get('SCRIPT_FILENAME', '');
            $segs = \explode('/', \trim($file, '/'));
            $segs = \array_reverse($segs);
            $index = 0;
            $last = \count($segs);
            $baseUrl = '';
            do {
                $seg = $segs[$index];
                $baseUrl = '/'.$seg.$baseUrl;
                ++$index;
            } while ($last > $index && (false !== $pos = \strpos($path, $baseUrl)) && 0 != $pos);
        }

        // Does the baseUrl have anything in common with the request_uri?
        $requestUri = $this->getRequestUri();
        if ('' !== $requestUri && '/' !== $requestUri[0]) {
            $requestUri = '/'.$requestUri;
        }

        if ($baseUrl && false !== $prefix = $this->getUrlencodedPrefix($requestUri, $baseUrl)) {
            // full $baseUrl matches
            return $prefix;
        }

        if ($baseUrl && false !== $prefix = $this->getUrlencodedPrefix($requestUri, \rtrim(\dirname($baseUrl), '/'.\DIRECTORY_SEPARATOR).'/')) {
            // directory portion of $baseUrl matches
            return \rtrim($prefix, '/'.\DIRECTORY_SEPARATOR);
        }

        $truncatedRequestUri = $requestUri;
        if (false !== $pos = \strpos($requestUri, '?')) {
            $truncatedRequestUri = \substr($requestUri, 0, $pos);
        }

        $basename = \basename($baseUrl);
        if (empty($basename) || !\strpos(\rawurldecode($truncatedRequestUri), $basename)) {
            // no match whatsoever; set it blank
            return '';
        }

        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of baseUrl. $pos !== 0 makes sure it is not matching a value
        // from PATH_INFO or QUERY_STRING
        if (\strlen($requestUri) >= \strlen($baseUrl) && (false !== $pos = \strpos($requestUri, $baseUrl)) && 0 !== $pos) {
            $baseUrl = \substr($requestUri, 0, $pos + \strlen($baseUrl));
        }

        return \rtrim($baseUrl, '/'.\DIRECTORY_SEPARATOR);
    }

    /**
     * Prepares the base path.
     *
     * @return string base path
     */
    protected function prepareBasePath()
    {
        $baseUrl = $this->getBaseUrl();
        if (empty($baseUrl)) {
            return '';
        }

        $filename = \basename($this->server->get('SCRIPT_FILENAME'));
        if (\basename($baseUrl) === $filename) {
            $basePath = \dirname($baseUrl);
        } else {
            $basePath = $baseUrl;
        }

        if ('\\' === \DIRECTORY_SEPARATOR) {
            $basePath = str_replace('\\', '/', $basePath);
        }

        return \rtrim($basePath, '/');
    }

    /**
     * Prepares the path info.
     *
     * @return string path info
     */
    protected function preparePathInfo()
    {
        if (null === ($requestUri = $this->getRequestUri())) {
            return '/';
        }

        // Remove the query string from REQUEST_URI
        if (false !== $pos = \strpos($requestUri, '?')) {
            $requestUri = \substr($requestUri, 0, $pos);
        }
        if ('' !== $requestUri && '/' !== $requestUri[0]) {
            $requestUri = '/'.$requestUri;
        }

        if (null === ($baseUrl = $this->getBaseUrl())) {
            return $requestUri;
        }

        $pathInfo = \substr($requestUri, \strlen($baseUrl));
        if (false === $pathInfo || '' === $pathInfo) {
            // If substr() returns false then PATH_INFO is set to an empty string
            return '/';
        }

        return (string)$pathInfo;
    }

    /**
     * Initializes HTTP request formats.
     */
    protected static function initializeFormats()
    {
        static::$formats = [
            'html'   => ['text/html', 'application/xhtml+xml'],
            'txt'    => ['text/plain'],
            'js'     => ['application/javascript', 'application/x-javascript', 'text/javascript'],
            'css'    => ['text/css'],
            'json'   => ['application/json', 'application/x-json'],
            'jsonld' => ['application/ld+json'],
            'xml'    => ['text/xml', 'application/xml', 'application/x-xml'],
            'rdf'    => ['application/rdf+xml'],
            'atom'   => ['application/atom+xml'],
            'rss'    => ['application/rss+xml'],
            'form'   => ['application/x-www-form-urlencoded'],
        ];
    }

    /*
     * Returns the prefix as encoded in the string when the string starts with
     * the given prefix, false otherwise.
     *
     * @return string|false The prefix as it is encoded in $string, or false
     */
    private function getUrlencodedPrefix($string, $prefix)
    {
        if (0 !== \strpos(\rawurldecode($string), $prefix)) {
            return false;
        }

        $len = \strlen($prefix);

        if (\preg_match(\sprintf('#^(%%[[:xdigit:]]{2}|.){%d}#', $len), $string, $match)) {
            return $match[0];
        }

        return false;
    }
}
