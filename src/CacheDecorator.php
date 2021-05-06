<?php

namespace CacheCompressor;

use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;

class CacheDecorator implements TagAwareAdapterInterface
{
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
        try {
            $item->set(gzcompress(serialize($item->get()), 9));
        } catch (\Throwable $e) {
        }

        return $item;
    }

    private function uncompress(CacheItemInterface $item): CacheItemInterface
    {
        $value = $item->get();

        if (!is_string($value)) {
            return $item;
        }
        if ($value === null) {
            return $item;
        }

        $item->set(
            unserialize(gzuncompress($value))
        );

        return $item;
    }

}
