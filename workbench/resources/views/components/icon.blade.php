@props(['name'])

{{ svg($name, $attributes->get('class', ''), $attributes->except('class')->getAttributes()) }}
