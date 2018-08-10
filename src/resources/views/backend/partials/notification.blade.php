<div class="notice notice-{{ $type }}{{ isset($dismiss) && $dismiss ? ' is-dismissible' : ''}}" {{isset($style) && $style ? 'style="'.$style.'"' : ''}}>
<p> {{ esc_html__( $message, TOPLYTICS_DOMAIN ) }}</p>
</div>
