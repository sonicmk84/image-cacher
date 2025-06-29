<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PornstarFeedValidator;

class PornstarFeedValidatorTest extends TestCase
{
    public function test_valid_pornstar_passes()
    {
        $item = [
            'id' => 1,
            'name' => 'Jane Doe',
            'link' => 'https://example.com',
            'license' => 'PUBLIC'
        ];

        $this->assertTrue(PornstarFeedValidator::validatePornstar($item));
    }

    public function test_invalid_pornstar_id_fails()
    {
        $item = [
            'id' => 'abc',
            'name' => 'Jane Doe',
            'link' => 'https://example.com',
            'license' => 'PUBLIC'
        ];

        $this->assertFalse(PornstarFeedValidator::validatePornstar($item));
    }

    public function test_invalid_pornstar_name_fails()
    {
        $item = [
            'id' => 2,
            'name' => 'Jane Doe!',
            'link' => 'https://example.com/',
            'license' => 'PUBLIC'
        ];

        $this->assertFalse(PornstarFeedValidator::validatePornstar($item));
    }

    public function test_invalid_pornstar_link_fails()
    {
        $item = [
            'id' => 3,
            'name' => 'John Doe',
            'link' => 'https://example.com (test)/',
            'license' => 'PUBLIC'
        ];

        $this->assertFalse(PornstarFeedValidator::validatePornstar($item));
    }

    public function test_invalid_pornstar_license_fails()
    {
        $item = [
            'id' => 4, // invalid
            'name' => 'John Doe',
            'link' => 'https://example.com/page',
            'license' => 'PUBL1C'
        ];

        $this->assertFalse(PornstarFeedValidator::validatePornstar($item));
    }

    public function test_valid_thumbnail_passes()
    {
        $thumb = [
            'type' => 'pc',
            'urls' => ['https://example.com/thumb.jpg']
        ];

        $this->assertTrue(PornstarFeedValidator::validateThumbnail($thumb));
    }

    public function test_invalid_thumbnail_type_fails()
    {
        $thumb = [
            'type' => 'car',
            'urls' => ['https://example.com/thumb.jpg']
        ];

        $this->assertFalse(PornstarFeedValidator::validateThumbnail($thumb));
    }

    public function test_invalid_thumbnail_urls_fails()
    {
        $thumb = [
            'type' => 'tablet',
            'urls' => ['not-a-url']
        ];

        $this->assertFalse(PornstarFeedValidator::validateThumbnail($thumb));
    }

    public function test_invalid_thumbnail_urls_unset_fails()
    {
        $thumb = [
            'type' => 'tablet', // no urls field
        ];

        $this->assertFalse(PornstarFeedValidator::validateThumbnail($thumb));
    }

    public function test_invalid_thumbnail_urls_not_array_fails()
    {
        $thumb = [
            'type' => 'tablet',
            'urls' => "abc"
        ];

        $this->assertFalse(PornstarFeedValidator::validateThumbnail($thumb));
    }

    public function test_invalid_thumbnail_urls_empty_fails()
    {
        $thumb = [
            'type' => 'tablet',
            'urls' => []
        ];

        $this->assertFalse(PornstarFeedValidator::validateThumbnail($thumb));
    }
}

