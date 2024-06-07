<?php
declare(strict_types=1);

namespace NiceYu\Validate;

/**
 * Request Parameters Info
 */
class Parameters
{
    /**
     * Request method
     * @var string
     */
    private string $method;

    /**
     * Request params
     * @var array
     */
    private array $params;

    /**
     * class construct
     */
    public function __construct()
    {
        $this->method   = $_SERVER['REQUEST_METHOD'] ?: 'GET';
        $this->params   = $this->getParameters();
    }

    /**
     * all params
     * @return array
     */
    public function all():array
    {
        return $this->params;
    }

    /**
     * get params
     * @return array
     */
    private function getParameters(): array
    {
        $content = file_get_contents('php://input');
        $contentType = $_SERVER['CONTENT_TYPE'];

        if (empty($content)) {
            if ($this->method === 'GET') {
                return $_GET ?: $_REQUEST;
            }
            return $_POST ?: $_REQUEST;
        }

        /** urlencoded */
        if ('application/x-www-form-urlencoded' == $contentType) {
            parse_str($content, $data);
            return $data;
        }

        /** application/json  */
        if (false !== strpos($contentType, 'json')) {
            $params =  (array) json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $params = [];
            }
            return $params;
        }

        /** Content-Disposition  */
        if (false !== strpos($content, 'Content-Disposition')) {

            /** Match boundaries */
            preg_match('/boundary=(.*)$/', $contentType, $matches);

            /** Split data */
            $parts = array_slice(explode("--$matches[1]", $content), 1);

            $params = array();
            foreach ($parts as $part) {
                /** If this is the last part, skip it (-- terminator) */
                if ($part == "--\r\n") break;

                /** Separate keys and values */
                list($rawHeaders, $rawContent) = explode("\r\n\r\n", $part, 2);

                $rawHeaders = explode("\r\n", $rawHeaders);
                foreach ($rawHeaders as $header) {
                    if (empty($header)) continue;

                    $match = '/^Content-Disposition: *form-data; *name="([^"]+)"(?:; *filename="([^"]+)")?/';
                    preg_match($match, $header, $matches);
                    $rawHeaders = $matches[1] ?? null;
                }

                /** Delete the last line break in the content */
                $params[$rawHeaders] = substr($rawContent, 0, strlen($rawContent) - 2);
            }
            return $params;
        }
        return [];
    }
}
