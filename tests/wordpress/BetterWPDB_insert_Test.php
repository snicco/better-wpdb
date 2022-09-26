<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use InvalidArgumentException;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Tests\BetterWPDBTestCase;
use Snicco\Component\BetterWPDB\Tests\fixtures\TestLogger;
use stdClass;

/**
 * @internal
 */
final class BetterWPDB_insert_Test extends BetterWPDBTestCase
{
    protected function tearDown(): void
    {
        $this->better_wpdb->preparedQuery('drop table if exists no_auto_incr');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_insert(): void
    {
        $this->assertRecordCount(0);

        $stmt = $this->better_wpdb->insert('test_table', [
            'test_string' => 'foo',
            'test_int' => 10,
        ]);

        $this->assertSame(1, $stmt->affected_rows);
        $this->assertSame(1, $stmt->insert_id);
        $this->assertRecordCount(1);
        $this->assertRecord(1, [
            'test_string' => 'foo',
            'test_int' => 10,
            'test_float' => null,
            'test_bool' => 0,
        ]);

        $stmt = $this->better_wpdb->insert('test_table', [
            'test_string' => 'bar',
            'test_int' => 20,
            'test_float' => 10.00,
            'test_bool' => true,
        ]);

        $this->assertSame(1, $stmt->affected_rows);
        $this->assertSame(2, $stmt->insert_id);
        $this->assertRecordCount(2);
        $this->assertRecord(2, [
            'test_string' => 'bar',
            'test_int' => 20,
            'test_float' => 10.00,
            'test_bool' => 1,
        ]);
    }

    /**
     * @test
     */
    public function test_insert_with_non_auto_incrementing_id(): void
    {
        $this->better_wpdb->preparedQuery(
            'CREATE TABLE IF NOT EXISTS `no_auto_incr` (
  `id` bigint unsigned NOT NULL,
  `test_string` varchar(30) COLLATE utf8mb4_unicode_520_ci UNIQUE NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;',
            []
        );

        $stmt = $this->better_wpdb->insert('no_auto_incr', [
            'id' => 10,
            'test_string' => 'foo',
        ]);
        $this->assertSame(1, $stmt->affected_rows);
        $this->assertSame(0, $stmt->insert_id);
    }

    /**
     * @test
     */
    public function test_insert_throws_exception_for_empty_table_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->better_wpdb->insert('', [
            'test_string' => 'foo',
        ]);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_insert_throws_exception_for_empty_data(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('empty array');

        $this->better_wpdb->insert('test_table', []);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_insert_throws_exception_for_non_string_column_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');

        $this->better_wpdb->insert('test_table', ['foo']);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_insert_throws_exception_for_empty_string_column_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');

        $this->better_wpdb->insert('test_table', [
            '' => 'foo',
        ]);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_insert_throws_exception_for_empty_non_scalar_data_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('scalar');

        $this->better_wpdb->insert('test_table', [
            'test_string' => new stdClass(),
        ]);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_insert_throws_exception_for_multi_dimensional_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty-string');

        $this->better_wpdb->insert('test_table', [[
            'test_string' => 'foo',
        ]]);
    }

    /**
     * @test
     */
    public function test_insert_is_logged(): void
    {
        $logger = new TestLogger();
        $db = BetterWPDB::fromWpdb($logger);

        $db->insert('test_table', [
            'test_string' => 'foo',
        ]);

        $this->assertTrue(isset($logger->queries[0]));
        $this->assertCount(1, $logger->queries);

        $this->assertSame(
            'insert into `test_table` (`test_string`) values (?)',
            $logger->queries[0]->sql_with_placeholders
        );
        $this->assertSame(['foo'], $logger->queries[0]->bindings);
        $this->assertGreaterThan($logger->queries[0]->start, $logger->queries[0]->end);
    }
}
