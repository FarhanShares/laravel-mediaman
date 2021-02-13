<?php

namespace FarhanShares\MediaMan\Tests;

use FarhanShares\MediaMan\MediaChannel;


class MediaChannelTest extends TestCase
{
    /** @test */
    public function it_can_register_and_retrieve_conversions()
    {
        $mediaChannel = new MediaChannel();

        $mediaChannel->performConversions('one', 'two');

        $registeredConversions = $mediaChannel->getConversions();

        $this->assertCount(2, $registeredConversions);
        $this->assertEquals(['one', 'two'], $registeredConversions);
    }

    /** @test */
    public function it_can_determine_if_any_conversions_have_been_registered()
    {
        $mediaChannel = new MediaChannel();

        $this->assertFalse($mediaChannel->hasConversions());

        $mediaChannel->performConversions('conversion');

        $this->assertTrue($mediaChannel->hasConversions());
    }
}
