<?php
// Somente no Playground de desenvolvimento. Não distribuir com o plugin.
add_filter('pre_wp_mail', static fn($return, $args) => true, 5, 2);
