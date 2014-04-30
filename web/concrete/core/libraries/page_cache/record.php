<?

defined('C5_EXECUTE') or die("Access Denied.");

/**
 * Class Concrete5_Library_PageCacheRecord
 */
class Concrete5_Library_PageCacheRecord {

    /**
     * @param Page $c
     * @param $content
     * @param $lifetime
     */
    public function __construct(Page $c, $content, $lifetime) {
		$cache = PageCache::getLibrary();
		$this->setCacheRecordLifetime($lifetime);
		$this->setCacheRecordKey($cache->getCacheKey($c));
		$this->setCacheRecordHeaders($cache->getCacheHeaders($c));
		$this->setCacheRecordContent($content);
	}

    /**
     * @param $lifetime
     */
    public function setCacheRecordLifetime($lifetime) {
		$this->expires = time() + $lifetime;
	}

    /**
     * @return mixed
     */
    public function getCacheRecordExpiration() {
		return $this->expires;
	}

    /**
     * @param $content
     */
    public function setCacheRecordContent($content) {
		$this->content = $content;
	}

    /**
     * @return mixed
     */
    public function getCacheRecordContent() {
		return $this->content;
	}

    /**
     * @param $headers
     */
    public function setCacheRecordHeaders($headers) {
		$this->headers = $headers;
	}

    /**
     * @return mixed
     */
    public function getCacheRecordHeaders() {
		return $this->headers;
	}

    /**
     * @return mixed
     */
    public function getCacheRecordKey() {
		return $this->cacheRecordKey;
	}

    /**
     * @param $cacheRecordKey
     */
    public function setCacheRecordKey($cacheRecordKey) {
		$this->cacheRecordKey = $cacheRecordKey;
	}

    /**
     * @return bool
     */
    public function validate() {
		$diff = $this->expires - time();
		if ($diff > 0) {
			// it's still valid
			return true;
		} else {
			// invalidate and kill this record.
			$cache = PageCache::getLibrary();
			$cache->purgeByRecord($this);
		}
	}
	

}