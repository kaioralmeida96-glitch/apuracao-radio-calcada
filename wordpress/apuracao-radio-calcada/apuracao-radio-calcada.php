<?php
/**
 * Plugin Name:       Apuração 2026 · Rádio Calçada
 * Description:       Apuração das Eleições 2026 com dados oficiais do TSE: 2º turno ao vivo, governadores, 1º turno e mapa. Página própria em /apuracao/ e shortcode [apuracao_2026] para usar em qualquer página ou post.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Author:            Rádio Calçada
 * License:           GPL-2.0-or-later
 * Text Domain:       apuracao-radio-calcada
 */

if (!defined('ABSPATH')) exit;

define('RC_APURACAO_FILE', __DIR__ . '/app/index.html');
define('RC_APURACAO_OPT', 'rc_apuracao_slug');
define('RC_APURACAO_HOME', 'rc_apuracao_home');
define('RC_APURACAO_ADS', 'rc_apuracao_ads');

// espaços de anúncio da página: nome no painel e tamanho recomendado (computador, celular)
function rc_apuracao_ad_slots() {
    return array(
        'topo'   => array('Topo (abaixo do menu)', '970 × 90', '320 × 100'),
        'meio'   => array('Meio (entre as seções)', '970 × 250', '300 × 250'),
        'rodape' => array('Rodapé', '970 × 90', '320 × 100'),
    );
}

function rc_apuracao_ads() {
    $ads = get_option(RC_APURACAO_ADS, array());
    return is_array($ads) ? $ads : array();
}

/* ---------- endereço próprio da apuração (ex.: seusite.com.br/apuracao/) ---------- */

function rc_apuracao_slug() {
    $slug = sanitize_title(get_option(RC_APURACAO_OPT, 'apuracao'));
    return $slug ? $slug : 'apuracao';
}

function rc_apuracao_url($args = array(), $hash = '') {
    // sem links permanentes ("Simples"), usa ?rc_apuracao=1, que funciona em qualquer WordPress
    $url = get_option('permalink_structure')
        ? home_url('/' . rc_apuracao_slug() . '/')
        : add_query_arg('rc_apuracao', '1', home_url('/'));
    if ($args) $url = add_query_arg($args, $url);
    return $url . ($hash ? '#' . $hash : '');
}

add_action('init', function () {
    add_rewrite_rule('^' . preg_quote(rc_apuracao_slug(), '#') . '/?$', 'index.php?rc_apuracao=1', 'top');
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'rc_apuracao';
    return $vars;
});

// entrega a aplicação inteira, sem o tema do WordPress em volta
// (no endereço próprio e, se marcado nas configurações, também na página inicial do site)
add_action('template_redirect', function () {
    $home = get_option(RC_APURACAO_HOME) && is_front_page() && !is_paged();
    if (!get_query_var('rc_apuracao') && !$home) return;
    if (!is_readable(RC_APURACAO_FILE)) {
        wp_die('Arquivo da apuração não encontrado no plugin. Reinstale o plugin Apuração 2026.');
    }
    $html = file_get_contents(RC_APURACAO_FILE);

    // dentro do shortcode a página roda num iframe: avisa a altura para o iframe crescer junto
    $resize = "<script>(function(){if(window.parent===window)return;var last=0;"
        . "function send(){var h=Math.ceil(document.body.getBoundingClientRect().height);if(h!==last){last=h;"
        . "window.parent.postMessage({rcApuracaoHeight:h},'*');}}"
        . "window.addEventListener('load',send);window.addEventListener('hashchange',function(){last=0;send();});"
        . "setInterval(send,700);})();</script>";
    $html = str_replace('</body>', $resize . "\n</body>", $html);

    // anúncios e QR code configurados no painel
    $ads = '<script>window.RC_ADS = ' . wp_json_encode(rc_apuracao_ads()) . ';</script>';
    $html = str_replace('</head>', $ads . "\n</head>", $html);

    status_header(200);
    nocache_headers();
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
});

register_activation_hook(__FILE__, function () {
    add_rewrite_rule('^' . preg_quote(rc_apuracao_slug(), '#') . '/?$', 'index.php?rc_apuracao=1', 'top');
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

/* ---------- shortcode: [apuracao_2026 aba="mapa"] ---------- */

add_shortcode('apuracao_2026', function ($atts) {
    $a = shortcode_atts(array(
        'aba'      => '2turno',  // 2turno, governadores, 1turno ou mapa
        'uf'       => '',        // ex.: sp (abre o 2º turno nesse estado)
        'simulado' => '',        // 1 = dados de teste do TSE
        'altura'   => '1600',    // altura inicial em pixels, antes de ajustar sozinho
    ), $atts, 'apuracao_2026');

    $abas = array('2turno', 'governadores', '1turno', 'mapa');
    $aba  = in_array($a['aba'], $abas, true) ? $a['aba'] : '2turno';
    $args = array();
    if ($a['uf'] !== '') $args['uf'] = preg_replace('/[^a-z]/', '', strtolower($a['uf']));
    if ($a['simulado'] === '1') $args['simulado'] = '1';

    static $n = 0;
    $n++;
    $id = 'rc-apuracao-' . $n;

    $html  = '<div class="rc-apuracao-embed" style="width:100%;max-width:100%;">';
    // endereço relativo: o iframe abre no mesmo domínio da página (www ou não, http ou https)
    $html .= '<iframe id="' . esc_attr($id) . '" src="' . esc_url(wp_make_link_relative(rc_apuracao_url($args, $aba))) . '"'
        . ' title="Apuração das Eleições 2026"'
        . ' style="display:block;width:100%;border:0;min-height:600px;height:' . intval($a['altura']) . 'px;background:#f2f6fb;"></iframe>';
    $html .= '</div>';
    $html .= '<script>(function(){var f=document.getElementById(' . wp_json_encode($id) . ');'
        . 'window.addEventListener("message",function(e){if(e.source===f.contentWindow&&e.data&&e.data.rcApuracaoHeight){'
        . 'f.style.height=e.data.rcApuracaoHeight+"px";}});})();</script>';
    return $html;
});

/* ---------- tela no painel: Configurações → Apuração 2026 ---------- */

add_action('admin_menu', function () {
    add_options_page('Apuração 2026', 'Apuração 2026', 'manage_options', 'rc-apuracao', 'rc_apuracao_admin_page');
});

add_action('admin_init', function () {
    register_setting('rc_apuracao', RC_APURACAO_OPT, array(
        'type' => 'string',
        'sanitize_callback' => function ($v) { $v = sanitize_title($v); return $v ? $v : 'apuracao'; },
        'default' => 'apuracao',
    ));
    register_setting('rc_apuracao', RC_APURACAO_HOME, array(
        'type' => 'boolean',
        'sanitize_callback' => function ($v) { return $v ? 1 : 0; },
        'default' => 0,
    ));
    register_setting('rc_apuracao', RC_APURACAO_ADS, array(
        'type' => 'array',
        'sanitize_callback' => 'rc_apuracao_sanitize_ads',
        'default' => array(),
    ));
});

function rc_apuracao_sanitize_ads($v) {
    $v = is_array($v) ? $v : array();
    $out = array();
    foreach (array_keys(rc_apuracao_ad_slots()) as $slot) {
        $s = isset($v[$slot]) && is_array($v[$slot]) ? $v[$slot] : array();
        $out[$slot] = array(
            'desktop' => esc_url_raw(isset($s['desktop']) ? trim($s['desktop']) : ''),
            'mobile'  => esc_url_raw(isset($s['mobile']) ? trim($s['mobile']) : ''),
            'link'    => esc_url_raw(isset($s['link']) ? trim($s['link']) : ''),
            'alt'     => sanitize_text_field(isset($s['alt']) ? $s['alt'] : ''),
        );
    }
    $q = isset($v['qr']) && is_array($v['qr']) ? $v['qr'] : array();
    $out['qr'] = array(
        'imagem' => esc_url_raw(isset($q['imagem']) ? trim($q['imagem']) : ''),
        'titulo' => sanitize_text_field(isset($q['titulo']) ? $q['titulo'] : ''),
        'texto'  => sanitize_text_field(isset($q['texto']) ? $q['texto'] : ''),
        'link'   => esc_url_raw(isset($q['link']) ? trim($q['link']) : ''),
    );
    return $out;
}

// biblioteca de mídia do WordPress para o botão "Escolher imagem"
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'settings_page_rc-apuracao') return;
    wp_enqueue_media();
});

// campo de imagem: endereço + botão que abre a biblioteca de mídia + miniatura
function rc_apuracao_image_field($name, $value, $hint) {
    ?>
    <div class="rc-img">
        <input type="url" class="regular-text rc-img-url" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" placeholder="https://…">
        <button type="button" class="button rc-img-pick">Escolher imagem</button>
        <button type="button" class="button-link rc-img-clear" style="color:#b32d2e;margin-left:6px">Remover</button>
        <p class="description"><?php echo esc_html($hint); ?></p>
        <img class="rc-img-prev" src="<?php echo esc_url($value); ?>" alt="" style="max-width:320px;max-height:120px;margin-top:6px;display:<?php echo $value ? 'block' : 'none'; ?>;border:1px solid #dcdcde">
    </div>
    <?php
}

// trocar o endereço exige atualizar as regras de link
add_action('update_option_' . RC_APURACAO_OPT, function () {
    add_rewrite_rule('^' . preg_quote(rc_apuracao_slug(), '#') . '/?$', 'index.php?rc_apuracao=1', 'top');
    flush_rewrite_rules();
});

function rc_apuracao_admin_page() {
    if (!current_user_can('manage_options')) return;
    $url = rc_apuracao_url();
    $is_home = (bool) get_option(RC_APURACAO_HOME);
    ?>
    <div class="wrap">
        <h1>Apuração 2026 · Rádio Calçada</h1>

        <h2>Página da apuração</h2>
        <p>A apuração completa (2º turno, governadores, 1º turno e mapa) já está no ar <?php echo $is_home ? 'na página inicial do site e também' : ''; ?> neste endereço:</p>
        <p>
            <?php if ($is_home) : ?>
                <a class="button button-primary" href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener"><?php echo esc_html(home_url('/')); ?></a>
            <?php endif; ?>
            <a class="button<?php echo $is_home ? '' : ' button-primary'; ?>" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener"><?php echo esc_html($url); ?></a>
        </p>

        <form method="post" action="options.php">
            <?php settings_fields('rc_apuracao'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Página inicial</th>
                    <td>
                        <label>
                            <input name="<?php echo esc_attr(RC_APURACAO_HOME); ?>" type="checkbox" value="1" <?php checked($is_home); ?>>
                            Usar a apuração como página inicial do site (<code><?php echo esc_html(home_url('/')); ?></code>)
                        </label>
                        <p class="description">Quem abrir o endereço principal do site vai direto para a apuração. As outras páginas, os posts e o painel continuam iguais. Para voltar à página inicial de antes, é só desmarcar.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rc_apuracao_slug">Endereço da página</label></th>
                    <td>
                        <code><?php echo esc_html(home_url('/')); ?></code>
                        <input name="<?php echo esc_attr(RC_APURACAO_OPT); ?>" id="rc_apuracao_slug" type="text" class="regular-text" value="<?php echo esc_attr(rc_apuracao_slug()); ?>">
                        <p class="description">Ex.: <code>apuracao</code>, <code>eleicoes-2026</code>. Só letras minúsculas, números e hífen.</p>
                    </td>
                </tr>
            </table>

            <?php $ads = rc_apuracao_ads(); $an = RC_APURACAO_ADS; ?>
            <h2>Anúncios</h2>
            <p>Cada espaço tem uma imagem para computador e outra para celular. Se mandar só uma, ela é usada nos dois. Espaço sem imagem não aparece no site.
               <a href="<?php echo esc_url(rc_apuracao_url(array('anuncios' => 'exemplo'))); ?>" target="_blank" rel="noopener">Ver onde ficam os espaços</a></p>
            <?php foreach (rc_apuracao_ad_slots() as $slot => $info) :
                $s = isset($ads[$slot]) ? $ads[$slot] : array();
                $g = function ($k) use ($s) { return isset($s[$k]) ? $s[$k] : ''; }; ?>
                <h3 style="margin-bottom:0"><?php echo esc_html($info[0]); ?></h3>
                <table class="form-table" role="presentation">
                    <tr><th scope="row">Banner computador</th><td><?php rc_apuracao_image_field("{$an}[{$slot}][desktop]", $g('desktop'), 'Tamanho recomendado: ' . $info[1] . ' px'); ?></td></tr>
                    <tr><th scope="row">Banner celular</th><td><?php rc_apuracao_image_field("{$an}[{$slot}][mobile]", $g('mobile'), 'Tamanho recomendado: ' . $info[2] . ' px'); ?></td></tr>
                    <tr><th scope="row">Link ao clicar</th><td><input type="url" class="regular-text" name="<?php echo esc_attr("{$an}[{$slot}][link]"); ?>" value="<?php echo esc_attr($g('link')); ?>" placeholder="https://site-do-anunciante.com.br"></td></tr>
                    <tr><th scope="row">Nome do anunciante</th><td><input type="text" class="regular-text" name="<?php echo esc_attr("{$an}[{$slot}][alt]"); ?>" value="<?php echo esc_attr($g('alt')); ?>"><p class="description">Texto alternativo da imagem (acessibilidade).</p></td></tr>
                </table>
            <?php endforeach; ?>

            <?php $q = isset($ads['qr']) ? $ads['qr'] : array(); $gq = function ($k) use ($q) { return isset($q[$k]) ? $q[$k] : ''; }; ?>
            <h2>QR code</h2>
            <p>Quadrado ao lado do anúncio do topo (no celular, logo abaixo dele).</p>
            <table class="form-table" role="presentation">
                <tr><th scope="row">Imagem do QR code</th><td><?php rc_apuracao_image_field("{$an}[qr][imagem]", $gq('imagem'), 'Imagem quadrada, de preferência 300 × 300 px'); ?></td></tr>
                <tr><th scope="row">Título</th><td><input type="text" class="regular-text" name="<?php echo esc_attr("{$an}[qr][titulo]"); ?>" value="<?php echo esc_attr($gq('titulo')); ?>" placeholder="Aponte a câmera"></td></tr>
                <tr><th scope="row">Texto</th><td><input type="text" class="large-text" name="<?php echo esc_attr("{$an}[qr][texto]"); ?>" value="<?php echo esc_attr($gq('texto')); ?>" placeholder="Ex.: Ouça a Rádio Calçada ao vivo"></td></tr>
                <tr><th scope="row">Link ao clicar</th><td><input type="url" class="regular-text" name="<?php echo esc_attr("{$an}[qr][link]"); ?>" value="<?php echo esc_attr($gq('link')); ?>" placeholder="https://"><p class="description">Opcional: quem estiver no celular pode tocar no QR code em vez de escanear.</p></td></tr>
            </table>

            <?php submit_button('Salvar configurações'); ?>
        </form>

        <script>
        // botão "Escolher imagem": abre a biblioteca de mídia e preenche o endereço
        (function ($) {
            $(document).on('click', '.rc-img-pick', function (e) {
                e.preventDefault();
                var box = $(this).closest('.rc-img');
                var frame = wp.media({ title: 'Escolher imagem', button: { text: 'Usar esta imagem' }, library: { type: 'image' }, multiple: false });
                frame.on('select', function () {
                    var url = frame.state().get('selection').first().toJSON().url;
                    box.find('.rc-img-url').val(url);
                    box.find('.rc-img-prev').attr('src', url).show();
                });
                frame.open();
            });
            $(document).on('click', '.rc-img-clear', function (e) {
                e.preventDefault();
                var box = $(this).closest('.rc-img');
                box.find('.rc-img-url').val('');
                box.find('.rc-img-prev').attr('src', '').hide();
            });
        })(jQuery);
        </script>

        <h2>Colocar dentro de uma página ou post</h2>
        <p>Cole um destes códigos em qualquer página, post ou bloco "Shortcode" do editor:</p>
        <table class="widefat striped" style="max-width:820px">
            <thead><tr><th>Código</th><th>O que mostra</th></tr></thead>
            <tbody>
                <tr><td><code>[apuracao_2026]</code></td><td>2º turno ao vivo (presidente, mapa e governadores)</td></tr>
                <tr><td><code>[apuracao_2026 aba="governadores"]</code></td><td>Governadores do 2º turno</td></tr>
                <tr><td><code>[apuracao_2026 aba="1turno"]</code></td><td>Resultado do 1º turno</td></tr>
                <tr><td><code>[apuracao_2026 aba="mapa"]</code></td><td>Mapa da apuração (1º e 2º turno)</td></tr>
                <tr><td><code>[apuracao_2026 uf="sp"]</code></td><td>2º turno já aberto em um estado</td></tr>
                <tr><td><code>[apuracao_2026 simulado="1"]</code></td><td>Teste com os dados fictícios do simulado do TSE</td></tr>
            </tbody>
        </table>
        <p>O menu de abas continua funcionando dentro da página, e a altura se ajusta sozinha.</p>
    </div>
    <?php
}

// atalho "Configurações" na lista de plugins
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    array_unshift($links, '<a href="' . esc_url(admin_url('options-general.php?page=rc-apuracao')) . '">Configurações</a>');
    return $links;
});
