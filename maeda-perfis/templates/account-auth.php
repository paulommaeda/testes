<?php
defined('ABSPATH') || exit;
use Maeda\Perfis\{Frontend, Plugin};
$generate_username = get_option('woocommerce_registration_generate_username') === 'yes';
$generate_password = get_option('woocommerce_registration_generate_password') === 'yes';
?>
<div class="pmp-auth-grid pmp-auth">
    <section class="pmp-card pmp-auth-card"><p class="pmp-eyebrow">JÁ TENHO UMA CONTA</p><h2>Bom ter você por aqui.</h2><p>Entre para acompanhar seus pedidos, testes e resultados.</p>
    <form class="woocommerce-form woocommerce-form-login login pmp-account-auth" method="post">
        <?php do_action('woocommerce_login_form_start'); ?>
        <p><label class="pmp-label" for="pmp-login-user">E-mail ou nome de usuário</label><input class="pmp-input input-text" id="pmp-login-user" name="username" autocomplete="username" required></p>
        <p><label class="pmp-label" for="pmp-login-password">Senha</label><input class="pmp-input input-text" type="password" id="pmp-login-password" name="password" autocomplete="current-password" required></p>
        <?php Frontend::auth_fields(); do_action('woocommerce_login_form'); wp_nonce_field('woocommerce-login','woocommerce-login-nonce'); ?>
        <div class="pmp-auth-options"><label><input type="checkbox" name="rememberme" value="forever"> Manter conectado</label><a href="<?php echo esc_url(wp_lostpassword_url(Plugin::url(['pmp_view'=>'login']))); ?>">Esqueci minha senha</a></div>
        <button type="submit" class="pmp-btn" name="login" value="1">Entrar na minha conta</button>
        <?php do_action('woocommerce_login_form_end'); ?>
    </form></section>
    <section class="pmp-card pmp-auth-card"><p class="pmp-eyebrow">MEU PRIMEIRO ACESSO</p><h2>Seu espaço começa aqui.</h2><p>Crie sua conta. Se recebeu um convite, use o mesmo e-mail do destinatário.</p>
    <form class="woocommerce-form woocommerce-form-register register pmp-account-auth" method="post">
        <?php do_action('woocommerce_register_form_start'); ?>
        <?php if (!$generate_username): ?><p><label class="pmp-label" for="reg_username">Nome de usuário</label><input class="pmp-input input-text" id="reg_username" name="username" autocomplete="username" required></p><?php endif; ?>
        <p><label class="pmp-label" for="reg_email">Seu e-mail</label><input class="pmp-input input-text" id="reg_email" type="email" name="email" autocomplete="email" required></p>
        <?php if (!$generate_password): ?><p><label class="pmp-label" for="reg_password">Crie uma senha</label><input class="pmp-input input-text" id="reg_password" type="password" name="password" autocomplete="new-password" required></p><?php else: ?><p class="pmp-auth-hint">Você receberá por e-mail um link para definir sua senha.</p><?php endif; ?>
        <?php Frontend::auth_fields(); do_action('woocommerce_register_form'); wp_nonce_field('woocommerce-register','woocommerce-register-nonce'); ?>
        <button type="submit" class="pmp-btn pmp-btn-secondary" name="register" value="1">Criar minha conta</button>
        <?php do_action('woocommerce_register_form_end'); ?>
    </form></section>
</div>
