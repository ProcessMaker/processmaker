<?php

namespace Tests\Unit\ProcessMaker\Models;

use PHPUnit\Framework\TestCase;
use ProcessMaker\Models\CallActivity;

class CallActivityMergeTest extends TestCase
{
    private function callActivity(): CallActivity
    {
        return new class extends CallActivity {
            public function callResolveUpdatedData($store, array $allData): array
            {
                return $this->resolveUpdatedData($store, $allData);
            }

            public function callMergeNewKeys(array $data, array $allData, array $parentData): array
            {
                return $this->mergeNewKeys($data, $allData, $parentData);
            }

            public function callMergeChangedKeys(array $data, array $allData, array $parentData): array
            {
                return $this->mergeChangedKeys($data, $allData, $parentData);
            }
        };
    }

    public function testResolveUpdatedDataReturnsAllWhenUpdatedIsNull(): void
    {
        $store = new class {
            public function getUpdated()
            {
                return null;
            }
        };

        $callActivity = $this->callActivity();
        $all = ['a' => 1, 'b' => 2];

        $result = $callActivity->callResolveUpdatedData($store, $all);

        $this->assertSame($all, $result);
    }

    public function testResolveUpdatedDataReturnsEmptyWhenUpdatedIsEmptyArray(): void
    {
        $store = new class {
            public function getUpdated(): array
            {
                return [];
            }
        };

        $callActivity = $this->callActivity();
        $all = ['a' => 1, 'b' => 2];

        $result = $callActivity->callResolveUpdatedData($store, $all);

        $this->assertSame([], $result);
    }

    public function testResolveUpdatedDataReturnsOnlyUpdatedKeys(): void
    {
        $store = new class {
            public function getUpdated(): array
            {
                return ['b'];
            }
        };

        $callActivity = $this->callActivity();
        $all = ['a' => 1, 'b' => 2, 'c' => 3];

        $result = $callActivity->callResolveUpdatedData($store, $all);

        $this->assertSame(['b' => 2], $result);
    }

    public function testMergeNewKeysAddsKeysAbsentInParent(): void
    {
        $callActivity = $this->callActivity();
        $data = ['a' => 1];
        $all = ['a' => 1, 'b' => 2, 'c' => 3];
        $parent = ['a' => 1];

        $result = $callActivity->callMergeNewKeys($data, $all, $parent);

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $result);
    }

    public function testMergeChangedKeysAddsDifferencesNotTracked(): void
    {
        $callActivity = $this->callActivity();
        $data = ['a' => 1]; // already tracked
        $all = ['a' => 1, 'b' => 99, 'c' => 'new'];
        $parent = ['a' => 1, 'b' => 2, 'c' => 'old'];

        $result = $callActivity->callMergeChangedKeys($data, $all, $parent);

        $this->assertSame(['a' => 1, 'b' => 99, 'c' => 'new'], $result);
    }
}
