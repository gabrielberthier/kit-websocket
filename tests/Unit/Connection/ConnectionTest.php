<?php

namespace Tests\Unit\Connection;

use Brash\Websocket\Connection\Connection;
use Brash\Websocket\Events\OnDataReceivedEvent;
use Brash\Websocket\Events\OnDisconnectEvent;
use Brash\Websocket\Events\OnNewConnectionOpenEvent;
use Brash\Websocket\Events\OnUpgradeEvent;
use Brash\Websocket\Events\Protocols\EventDispatcher;
use Brash\Websocket\Events\Protocols\ListenerProvider;
use Brash\Websocket\Frame\Enums\CloseFrameEnum;
use Brash\Websocket\Handlers\OnUpgradeHandler;
use Brash\Websocket\Message\MessageWriter;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function React\Promise\resolve;
use function Tests\Helpers\getHandshake;



function getMockEventDispatcher(): LegacyMockInterface|MockInterface|EventDispatcherInterface
{
    $listenerProvider = new ListenerProvider();
    $listenerProvider->addListener(OnUpgradeEvent::class, new OnUpgradeHandler());

    return new EventDispatcher(
        $listenerProvider
    );
}

function createSut(EventDispatcherInterface|MockInterface $eventDispatcher = null): Connection
{
    $eventDispatcher ??= getMockEventDispatcher();

    return new Connection(
        $eventDispatcher,
        spy(MessageWriter::class),
        '127.0.0.1'
    );
}

test('Should receive handshake correctly and dispatch upgrade event', function (): void {
    $sut = createSut();
    $sut->getEventDispatcher();
    $sut->onMessage(getHandshake());
    expect($sut->isHandshakeDone())->toBeTrue();
});

test('Should call OnDataReceivedEvent successfully after handshake', function (): void {
    $dispatcher = mock(EventDispatcherInterface::class);
    $dispatcher->expects('dispatch')->with(OnDataReceivedEvent::class)->andReturn(resolve(null));
    

    $connection = createSut($dispatcher);
    $connection->completeHandshake();
    $connection->onMessage('blablabla');

    expect($connection->isHandshakeDone())->toBeTrue();

});

test('Should call on disconnect correctly after successful handshake', function (): void {
    $dispatcher = mock(EventDispatcherInterface::class);
    
    $dispatcher->shouldReceive('dispatch')->with(OnDisconnectEvent::class)->andReturn(resolve(null));
    
    $connection = createSut($dispatcher);
    $connection->completeHandshake();
    $connection->onEnd();

    expect($connection->isHandshakeDone())->toBeTrue();

});

test('Should call early desconnection when absent handshake', function (): void {
    $dispatcher = mock(EventDispatcherInterface::class);
    $dispatcher->shouldNotReceive('dispatch')->withAnyArgs();

    $connection = createSut($dispatcher);
    $connection->onEnd();

    expect($connection->isHandshakeDone())->toBeFalse();
});

test('Should call close correctly', function (): void {
    $connection = createSut();
    /** @var MockInterface */
    $writer = $connection->getSocketWriter();
    $writer->expects('close')->with(CloseFrameEnum::CLOSE_NORMAL, null);
    expect($connection->close(CloseFrameEnum::CLOSE_NORMAL))->toBeNull();
});
