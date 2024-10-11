<?php

declare(strict_types=1);

namespace Tests\Unit\Frame;

use Kit\Websocket\Frame\FrameBuilder;
use Kit\Websocket\Frame\PayloadLengthDto;

$sut = new FrameBuilder(50000);

test('Should convert a string frame and return content', function () use ($sut): void {

    $byteString = chr(100);
    $result = $sut->getPayloadLength("0{$byteString}");

    expect($result)->toEqual(new PayloadLengthDto(7, 100, 1, false));
});

test('Should convert a string frame with second byte as 126 and return content based on next two bytes', function () use ($sut): void {
    $byteString = chr(126);
    $result = $sut->getPayloadLength("0{$byteString}11");

    expect($result)->toEqual(new PayloadLengthDto(
        size: 23,
        length: 126,
        threshold: 3,
        force8Bits: false,
    ));
});

test('Should convert a string frame with second byte as 127 and return content based on next eight bytes', function () use ($sut): void {
    $byteString = chr(127);
    $result = $sut->getPayloadLength("0{$byteString}11111111");

    expect($result)->toEqual(new PayloadLengthDto(
        size: 71,
        length: 127,
        threshold: 9,
        force8Bits: true,
    ));
});

test('Should expect error when byte sequence is smaller than message frame', function () use ($sut): void {
    $byteString = chr(127);

    expect($sut->getPayloadLength("0{$byteString}111111"))->toBeNull();
});
