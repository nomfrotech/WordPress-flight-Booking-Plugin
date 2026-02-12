<?php

declare(strict_types=1);

namespace WFBP\Elementor;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

final class FlightSearchWidget extends Widget_Base
{
    public function get_name(): string
    {
        return 'wfbp-flight-search';
    }

    public function get_title(): string
    {
        return __('Flight Search', 'wfbp');
    }

    public function get_icon(): string
    {
        return 'eicon-site-search';
    }

    public function get_categories(): array
    {
        return ['general'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('section_content', ['label' => __('Settings', 'wfbp')]);

        $this->add_control('layout', [
            'label' => __('Layout', 'wfbp'),
            'type' => Controls_Manager::SELECT,
            'default' => 'horizontal',
            'options' => [
                'horizontal' => __('Horizontal', 'wfbp'),
                'vertical' => __('Vertical', 'wfbp'),
            ],
        ]);

        $this->add_control('default_trip_type', [
            'label' => __('Default Trip Type', 'wfbp'),
            'type' => Controls_Manager::SELECT,
            'default' => 'oneway',
            'options' => [
                'oneway' => __('One Way', 'wfbp'),
                'roundtrip' => __('Roundtrip', 'wfbp'),
            ],
        ]);

        $this->end_controls_section();
    }

    protected function render(): void
    {
        echo do_shortcode('[wfbp_search]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
