<?php
namespace Onebip\DateTime;

use MongoDate;
use PHPUnit_Framework_TestCase;

class UTCDateTimeRangeTest extends PHPUnit_Framework_TestCase
{
    public function testItCanBuildAClosedInterval()
    {
        $range = UTCDateTimeRange::fromIncludedToIncluded(
            UTCDateTime::box('1985-05-21'),
            UTCDateTime::box('2015-05-21')
        );

        $this->assertEquals(
            [
                '$gte' => new MongoDate(485481600),
                '$lte' => new MongoDate(1432166400),
            ],
            $range->toMongoQuery()
        );
    }

    public function testItCanBuildARightOpenInterval()
    {
        $range = UTCDateTimeRange::fromIncludedToExcluded(
            UTCDateTime::box('1985-05-21'),
            UTCDateTime::box('2015-05-21')
        );

        $this->assertEquals(
            [
                '$gte' => new MongoDate(485481600),
                '$lt' => new MongoDate(1432166400),
            ],
            $range->toMongoQuery()
        );
    }

    public function testToMongoQueryOnFieldCanUseACallbackFormatter()
    {
        $range = UTCDateTimeRange::fromIncludedToIncluded(
            UTCDateTime::box('1985-05-21'),
            UTCDateTime::box('2015-05-21')
        );

        $this->assertEquals(
            [
                'goofy' => [
                    '$gte' => '1985-05-21 00',
                    '$lte' => '2015-05-21 00',
                ]
            ],
            $range->toMongoQueryOnField('goofy', function(UTCDateTime $date) {
                return $date->toDateTime()->format('Y-m-d H');
            })
        );
    }

    public function testToMongoQueryOnFieldShouldReturnTheSameQueryTheNotParameterizedVersion()
    {
        $range = UTCDateTimeRange::fromIncludedToIncluded(
            UTCDateTime::box('1985-05-21'),
            UTCDateTime::box('2015-05-21')
        );

        $this->assertEquals(
            [
                'goofy' => [
                    '$gte' => new MongoDate(485481600),
                    '$lte' => new MongoDate(1432166400),
                ]
            ],
            $range->toMongoQueryOnField('goofy')
        );
    }

    public function testItCanBeFormattedWithACallback()
    {
        $range = UTCDateTimeRange::fromIncludedToExcluded(
            UTCDateTime::box('1985-05-21 10:00'),
            UTCDateTime::box('2015-05-21 12:00')
        );

        $callback = function (UTCDateTime $date) {
            return $date->toDateTime()->format('Y-m-d H');
        };

        $this->assertEquals(
            [
                '$gte' => '1985-05-21 10',
                '$lt' => '2015-05-21 12',
            ],
            $range->toMongoQuery($callback)
        );
    }

    public function testItCanExposeFrom()
    {
        $range = UTCDateTimeRange::fromIncludedToExcluded(
            $from = UTCDateTime::box('1985-05-21 10:00'),
            UTCDateTime::box('2015-05-21 12:00')
        );

        $this->assertEquals($from, $range->from());
    }

    public function testItCanBeConvertedInApiFormat()
    {
        $range = UTCDateTimeRange::fromIncludedToExcluded(
            UTCDateTime::box('2015-01-01'),
            UTCDateTime::box('2015-01-02')
        );

        $this->assertEquals('20150101000000..20150102000000', $range->toApiFormat());
    }

    public function testHourExcludedRangeGenerator()
    {
        $range = UTCDateTimeRange::fromIncludedToExcluded(
            UTCDateTime::box('2015-01-01 03:00'),
            UTCDateTime::box('2015-01-01 05:00')
        );

        $this->assertEquals(
            [
                UTCDateTime::box('2015-01-01 03:00'),
                UTCDateTime::box('2015-01-01 04:00'),
            ],
            iterator_to_array($range->iteratorOnHours())
        );
    }

    public function testHourIncludedRangeGenerator()
    {
        $range = UTCDateTimeRange::fromIncludedToIncluded(
            UTCDateTime::box('2015-01-01 03:00'),
            UTCDateTime::box('2015-01-01 05:00')
        );

        $this->assertEquals(
            [
                UTCDateTime::box('2015-01-01 03:00'),
                UTCDateTime::box('2015-01-01 04:00'),
                UTCDateTime::box('2015-01-01 05:00'),
            ],
            iterator_to_array($range->iteratorOnHours())
        );
    }

    public function testDayExcludedRangeGenerator()
    {
        $range = UTCDateTimeRange::fromIncludedToExcluded(
            UTCDateTime::box('2015-01-01 03:00'),
            UTCDateTime::box('2015-01-05 03:00')
        );

        $this->assertEquals(
            [
                UTCDateTime::box('2015-01-01 03:00'),
                UTCDateTime::box('2015-01-03 03:00'),
            ],
            iterator_to_array($range->iterateOnDays(2))
        );
    }

    public function testDayIncludedRangeGenerator()
    {
        $range = UTCDateTimeRange::fromIncludedToIncluded(
            UTCDateTime::box('2015-01-01 03:00'),
            UTCDateTime::box('2015-01-03 05:00')
        );

        $this->assertEquals(
            [
                UTCDateTime::box('2015-01-01 03:00'),
                UTCDateTime::box('2015-01-02 03:00'),
                UTCDateTime::box('2015-01-03 03:00'),
            ],
            iterator_to_array($range->iterateOnDays())
        );
    }

    public function testMonthExcludedRangeGenerator()
    {
        $range = UTCDateTimeRange::fromIncludedToExcluded(
            UTCDateTime::box('2015-01-01 03:00'),
            UTCDateTime::box('2015-05-01 03:00')
        );

        $this->assertEquals(
            [
                UTCDateTime::box('2015-01-01 03:00'),
                UTCDateTime::box('2015-03-01 03:00'),
            ],
            iterator_to_array($range->iterateOnMonths(2))
        );
    }

    public function testMonthIncludedRangeGenerator()
    {
        $range = UTCDateTimeRange::fromIncludedToIncluded(
            UTCDateTime::box('2015-01-01 03:00'),
            UTCDateTime::box('2015-04-01 05:00')
        );

        $this->assertEquals(
            [
                UTCDateTime::box('2015-01-01 03:00'),
                UTCDateTime::box('2015-02-01 03:00'),
                UTCDateTime::box('2015-03-01 03:00'),
                UTCDateTime::box('2015-04-01 03:00'),
            ],
            iterator_to_array($range->iterateOnMonths())
        );
    }

    public static function debugInfoExamples()
    {
        return [
            [
                UTCDateTimeRange::fromIncludedToIncluded(
                    UTCDateTime::box('2015-01-01 03:00:00.123456'),
                    UTCDateTime::box('2015-04-01 05:00:00.123456')
                ),
                'UTCDateTimeRange: [2015-01-01T03:00:00.123456+0000,2015-04-01T05:00:00.123456+0000]',
            ],
            [
                UTCDateTimeRange::fromIncludedToExcluded(
                    UTCDateTime::box('2015-01-01 03:00:00.123456'),
                    UTCDateTime::box('2015-04-01 05:00:00.123456')
                ),
                'UTCDateTimeRange: [2015-01-01T03:00:00.123456+0000,2015-04-01T05:00:00.123456+0000)',
            ],
        ];
    }

    /**
     * @dataProvider debugInfoExamples
     */
    public function testDebugInfo(UTCDateTimeRange $range, $expected)
    {
        $this->assertEquals($expected, $range->__debugInfo());
    }
}
