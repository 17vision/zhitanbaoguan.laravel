<?php

namespace Tests\Unit;

use App\Models\Workflow;
use PHPUnit\Framework\TestCase;

class WorkflowTest extends TestCase
{
    /**
     * Test getCoverAttribute when cover is set and not empty.
     */
    public function test_get_cover_attribute_with_valid_cover(): void
    {
        $workflow = new Workflow();
        $workflow->attributes['cover'] = 'uploads/workflows/test-image.jpg';

        $result = $workflow->getCoverAttribute();

        $this->assertEquals('https://dkj.17vision.com/uploads/workflows/test-image.jpg', $result);
    }

    /**
     * Test getCoverAttribute when cover is set but empty string.
     */
    public function test_get_cover_attribute_with_empty_string(): void
    {
        $workflow = new Workflow();
        $workflow->attributes['cover'] = '';

        $result = $workflow->getCoverAttribute();

        $this->assertEquals('', $result);
    }

    /**
     * Test getCoverAttribute when cover is not set.
     */
    public function test_get_cover_attribute_when_not_set(): void
    {
        $workflow = new Workflow();
        $workflow->attributes = [];

        $result = $workflow->getCoverAttribute();

        $this->assertEquals('', $result);
    }

    /**
     * Test getCoverAttribute when cover is null.
     */
    public function test_get_cover_attribute_with_null(): void
    {
        $workflow = new Workflow();
        $workflow->attributes['cover'] = null;

        $result = $workflow->getCoverAttribute();

        $this->assertEquals('', $result);
    }

    /**
     * Test getCoverAttribute with different valid cover paths.
     */
    public function test_get_cover_attribute_with_various_paths(): void
    {
        $workflow = new Workflow();

        // Test with nested path
        $workflow->attributes['cover'] = 'images/2024/03/workflow-cover.png';
        $this->assertEquals(
            'https://dkj.17vision.com/images/2024/03/workflow-cover.png',
            $workflow->getCoverAttribute()
        );

        // Test with simple filename
        $workflow->attributes['cover'] = 'cover.jpg';
        $this->assertEquals(
            'https://dkj.17vision.com/cover.jpg',
            $workflow->getCoverAttribute()
        );

        // Test with absolute-like path (should still be appended)
        $workflow->attributes['cover'] = '/static/covers/default.jpg';
        $this->assertEquals(
            'https://dkj.17vision.com//static/covers/default.jpg',
            $workflow->getCoverAttribute()
        );
    }

    /**
     * Test getCoverAttribute with zero value (should return empty string).
     */
    public function test_get_cover_attribute_with_zero_value(): void
    {
        $workflow = new Workflow();
        $workflow->attributes['cover'] = 0;

        $result = $workflow->getCoverAttribute();

        $this->assertEquals('', $result);
    }

    /**
     * Test getCoverAttribute with falsy string that is not empty.
     */
    public function test_get_cover_attribute_with_falsy_string(): void
    {
        $workflow = new Workflow();
        $workflow->attributes['cover'] = 'false';

        $result = $workflow->getCoverAttribute();

        $this->assertEquals('https://dkj.17vision.com/false', $result);
    }
}
