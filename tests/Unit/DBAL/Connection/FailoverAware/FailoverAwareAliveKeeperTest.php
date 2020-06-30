<?php
declare(strict_types=1);
/*
 * @author     mfris
 * @copyright  PIXELFEDERATION s.r.o.
 * @license    Internal use only
 */

namespace PixelFederation\DoctrineResettableEmBundle\Tests\Unit\DBAL\Connection\FailoverAware;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use Exception;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware\ConnectionType;
use PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware\FailoverAwareAliveKeeper;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;

class FailoverAwareAliveKeeperTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testKeepAliveWriterWithoutReconnect(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $statementProphecy = $this->prophesize(Statement::class);
        $statementProphecy->fetchColumn(0)->willReturn('0')->shouldBeCalled();

        /** @var $connectionProphecy Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->query(Argument::any())->willReturn($statementProphecy->reveal())->shouldBeCalled();
        $connectionProphecy->close()->shouldNotBeCalled();
        $connectionProphecy->connect()->shouldNotBeCalled();

        $aliveKeeper = new FailoverAwareAliveKeeper(
            $loggerProphecy->reveal(),
            $connectionProphecy->reveal(),
            'default'
        );
        $aliveKeeper->keepAlive();
    }

    /**
     * @throws Exception
     */
    public function testKeepAliveReaderWithoutReconnect(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $statementProphecy = $this->prophesize(Statement::class);
        $statementProphecy->fetchColumn(0)->willReturn('1')->shouldBeCalled();

        /** @var $connectionProphecy Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->query(Argument::any())->willReturn($statementProphecy->reveal())->shouldBeCalled();
        $connectionProphecy->close()->shouldNotBeCalled();
        $connectionProphecy->connect()->shouldNotBeCalled();

        $aliveKeeper = new FailoverAwareAliveKeeper(
            $loggerProphecy->reveal(),
            $connectionProphecy->reveal(),
            'default',
            ConnectionType::READER
        );
        $aliveKeeper->keepAlive();
    }

    /**
     * @throws Exception
     */
    public function testKeepAliveWriterWithReconnectOnFailover(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->alert(Argument::any())->shouldBeCalled();
        $statementProphecy = $this->prophesize(Statement::class);
        $statementProphecy->fetchColumn(0)->willReturn('1')->shouldBeCalled();

        /** @var $connectionProphecy Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->query(Argument::any())->willReturn($statementProphecy->reveal())->shouldBeCalled();
        $connectionProphecy->close()->shouldBeCalled();
        $connectionProphecy->connect()->shouldBeCalled();

        $aliveKeeper = new FailoverAwareAliveKeeper(
            $loggerProphecy->reveal(),
            $connectionProphecy->reveal(),
            'default'
        );
        $aliveKeeper->keepAlive();
    }

    /**
     * @throws Exception
     */
    public function testKeepAliveReaderWithReconnectOnFailover(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->alert(Argument::any())->shouldBeCalled();
        $statementProphecy = $this->prophesize(Statement::class);
        $statementProphecy->fetchColumn(0)->willReturn('0')->shouldBeCalled();

        /** @var $connectionProphecy Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->query(Argument::any())->willReturn($statementProphecy->reveal())->shouldBeCalled();
        $connectionProphecy->close()->shouldBeCalled();
        $connectionProphecy->connect()->shouldBeCalled();

        $aliveKeeper = new FailoverAwareAliveKeeper(
            $loggerProphecy->reveal(),
            $connectionProphecy->reveal(),
            'default',
            ConnectionType::READER
        );
        $aliveKeeper->keepAlive();
    }

    /**
     * @throws Exception
     */
    public function testKeepAliveWithReconnectConnectionError(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->critical(Argument::any())->shouldBeCalled();
        $statementProphecy = $this->prophesize(Statement::class);
        $statementProphecy->fetchColumn(0)->willThrow(DBALException::class)->shouldBeCalled();

        /** @var $connectionProphecy Connection|ObjectProphecy */
        $connectionProphecy = $this->prophesize(Connection::class);
        $connectionProphecy->query(Argument::any())->willReturn($statementProphecy->reveal())->shouldBeCalled();
        $connectionProphecy->close()->shouldBeCalled();
        $connectionProphecy->connect()->shouldBeCalled();

        $aliveKeeper = new FailoverAwareAliveKeeper(
            $loggerProphecy->reveal(),
            $connectionProphecy->reveal(),
            'default'
        );
        $aliveKeeper->keepAlive();
    }
}