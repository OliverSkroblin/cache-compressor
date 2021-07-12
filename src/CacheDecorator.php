<?php

namespace CacheCompressor;

use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

class CacheDecorator implements TagAwareAdapterInterface
{
    private const COMPRESSED_MARKER = 'c!';

    /**
     * @var TagAwareAdapterInterface
     */
    private $decorated;

    public function __construct(TagAwareAdapterInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function getItem($key)
    {
        $item = $this->decorated->getItem($key);

        return $this->uncompress($item);
    }

    public function getItems(array $keys = [])
    {
        $items = $this->decorated->getItems($keys);

        $mapped = [];
        foreach ($items as $item) {
            $mapped[] = $this->uncompress($item);
        }

        return $mapped;
    }

    public function clear()
    {
        return $this->decorated->clear();
    }

    public function hasItem($key)
    {
        return $this->decorated->hasItem($key);
    }

    public function deleteItem($key)
    {
        return $this->decorated->deleteItem($key);
    }

    public function deleteItems(array $keys)
    {
        return $this->decorated->deleteItems($keys);
    }

    public function save(CacheItemInterface $item)
    {
        return $this->decorated->save(
            $this->compress($item)
        );
    }

    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->decorated->saveDeferred($item);
    }

    public function commit()
    {
        return $this->decorated->commit();
    }

    public function invalidateTags(array $tags)
    {
        return $this->decorated->invalidateTags($tags);
    }

    private function compress(CacheItemInterface $item): CacheItemInterface
    {
        $data = serialize($item->get());

        try {
            $compressedData = self::COMPRESSED_MARKER . gzcompress($data, 9);
            if (strlen($data) > strlen($compressedData)) {
                $data = $compressedData;
            }
        } catch (\Throwable $e) {
        }

        $item->set($data);

        return $item;
    }

    private function uncompress(CacheItemInterface $item): CacheItemInterface
    {
        $value = $item->get();

        if (!is_string($value)) {
            return $item;
        }

        if (strpos($value, self::COMPRESSED_MARKER) === 0) {
            $value = substr($value, strlen(self::COMPRESSED_MARKER));
            $value = gzuncompress($value);
        }

        try {
            $item->set(
                unserialize($value)
            );
        } catch(\Exception $e) {
            // this may be an entry saved deferred
        }

        return $item;
    }

}
