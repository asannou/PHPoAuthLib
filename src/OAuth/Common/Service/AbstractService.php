<?php

namespace OAuth\Common\Service;

use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Exception\Exception;
use OAuth\Common\Storage\TokenStorageInterface;

/**
 * Abstract OAuth service, version-agnostic
 */
abstract class AbstractService implements ServiceInterface
{
    /** @var Credentials */
    protected $credentials;

    /** @var ClientInterface */
    protected $httpClient;

    /** @var TokenStorageInterface */
    protected $storage;

    /**
     * @param CredentialsInterface  $credentials
     * @param ClientInterface       $httpClient
     * @param TokenStorageInterface $storage
     */
    public function __construct(
        CredentialsInterface $credentials,
        ClientInterface $httpClient,
        TokenStorageInterface $storage
    ) {
        $this->credentials = $credentials;
        $this->httpClient = $httpClient;
        $this->storage = $storage;
    }

    /**
     * @param UriInterface|string $path
     * @param UriInterface        $baseApiUri
     *
     * @return UriInterface
     *
     * @throws Exception
     */
    protected function determineRequestUriFromPath($path, UriInterface $baseApiUri = null)
    {
        if ($path instanceof UriInterface) {
            $uri = $path;
        } elseif (stripos($path, 'http://') === 0 || stripos($path, 'https://') === 0) {
            $uri = new Uri($path);
        } else {
            if (null === $baseApiUri) {
                throw new Exception(
                    'An absolute URI must be passed to ServiceInterface::request as no baseApiUri is set.'
                );
            }

            $uri = clone $baseApiUri;
            if (false !== strpos($path, '?')) {
                $parts = explode('?', $path, 2);
                $path = $parts[0];
                $query = $parts[1];
                $uri->setQuery($query);
            }

            if ($path[0] === '/') {
                $path = substr($path, 1);
            }

            $uri->setPath($uri->getPath() . $path);
        }

        return $uri;
    }

    /**
     * Accessor to the storage adapter to be able to retrieve tokens
     *
     * @return TokenStorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @return string
     */
    public function service()
    {
        // get class name without backslashes
        $classname = get_class($this);

        return preg_replace('/^.*\\\\/', '', $classname);
    }

    /**
     * Generates a random string
     *
     * @param int $length
     * @return string
     */
    public function generateRandomString($length)
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $random_bytes = openssl_random_pseudo_bytes($length);
        } else {
            $random_bytes = $this->generateRandomBytes($length);
        }
        $random_string = strtr(
            base64_encode($random_bytes),
            array(
                '+' => '-',
                '/' => '_',
                '=' => '',
            )
        );
        return substr($random_string, 0, $length);
    }

    /**
     * Generates random bytes
     *
     * @param int $length
     * @return string
     */
    public function generateRandomBytes($length)
    {
        $random_bytes = '';
        for ($i = 0; $i < $length / 4; $i++) {
            $random_number = mt_rand(-(mt_getrandmax() + 1), mt_getrandmax());
            $random_bytes .= pack('i', $random_number);
        }
        return $random_bytes;
    }
}
