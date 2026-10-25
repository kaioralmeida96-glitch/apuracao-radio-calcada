<?php
/**
 * Plugin Name:       Apuração Eleições 2026
 * Description:       Apuração das Eleições 2026 com dados oficiais do TSE: 2º turno ao vivo, governadores, 1º turno e mapa. Página própria em /apuracao/ e shortcode [apuracao_2026] para usar em qualquer página ou post.
 * Version:           1.2.0
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Author:            Apuração Eleições 2026
 * License:           GPL-2.0-or-later
 * Text Domain:       apuracao-eleicoes
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

// banners de um espaço (lista do carrossel); aceita o formato antigo, com um banner só
function rc_apuracao_ad_items($ads, $slot) {
    $v = isset($ads[$slot]) && is_array($ads[$slot]) ? $ads[$slot] : array();
    if (isset($v['desktop']) || isset($v['mobile'])) $v = array($v);
    return array_values(array_filter($v, 'is_array'));
}

function rc_apuracao_sanitize_ads($v) {
    $v = is_array($v) ? $v : array();
    $out = array();
    $s = isset($v['intervalo']) ? intval($v['intervalo']) : 6;
    $out['intervalo'] = ($s >= 2 && $s <= 60) ? $s : 6;
    $out['anuncie'] = empty($v['anuncie']) ? 0 : 1;
    $out['anuncie_texto'] = sanitize_text_field(isset($v['anuncie_texto']) ? $v['anuncie_texto'] : '');
    $out['anuncie_link'] = esc_url_raw(isset($v['anuncie_link']) ? trim($v['anuncie_link']) : '', array('http', 'https', 'mailto', 'tel'));
    foreach (array_keys(rc_apuracao_ad_slots()) as $slot) {
        $out[$slot] = array();
        foreach (rc_apuracao_ad_items($v, $slot) as $it) {
            $item = array(
                'desktop' => esc_url_raw(isset($it['desktop']) ? trim($it['desktop']) : ''),
                'mobile'  => esc_url_raw(isset($it['mobile']) ? trim($it['mobile']) : ''),
                'link'    => esc_url_raw(isset($it['link']) ? trim($it['link']) : ''),
                'alt'     => sanitize_text_field(isset($it['alt']) ? $it['alt'] : ''),
            );
            // banner sem nenhuma imagem é descartado
            if ($item['desktop'] !== '' || $item['mobile'] !== '') $out[$slot][] = $item;
        }
    }
    $out['qr'] = array();
    foreach (rc_apuracao_qr_items($v) as $q) {
        $out['qr'][] = array(
            'imagem' => esc_url_raw(isset($q['imagem']) ? trim($q['imagem']) : ''),
            'titulo' => sanitize_text_field(isset($q['titulo']) ? $q['titulo'] : ''),
            'texto'  => sanitize_text_field(isset($q['texto']) ? $q['texto'] : ''),
            'link'   => esc_url_raw(isset($q['link']) ? trim($q['link']) : ''),
        );
    }
    return $out;
}

// até 2 QR codes; aceita também o formato antigo, com um só
function rc_apuracao_qr_items($ads) {
    $v = isset($ads['qr']) && is_array($ads['qr']) ? $ads['qr'] : array();
    if (isset($v['imagem']) || isset($v['titulo'])) $v = array($v);
    return array_slice(array_values(array_filter($v, 'is_array')), 0, 2);
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

// um banner do carrossel: imagem de computador, de celular, link e nome do anunciante
function rc_apuracao_ad_card($slot, $i, $item, $info) {
    $n = RC_APURACAO_ADS . "[{$slot}][{$i}]";
    $g = function ($k) use ($item) { return isset($item[$k]) ? $item[$k] : ''; };
    ?>
    <div class="rc-card" style="background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:4px 14px 10px;margin:10px 0;max-width:900px">
        <p style="display:flex;justify-content:space-between;align-items:center;margin:8px 0 0">
            <strong class="rc-card-title">Banner</strong>
            <button type="button" class="button-link rc-card-del" style="color:#b32d2e">Remover este banner</button>
        </p>
        <table class="form-table" role="presentation" style="margin-top:0">
            <tr><th scope="row">Banner computador</th><td><?php rc_apuracao_image_field("{$n}[desktop]", $g('desktop'), 'Tamanho recomendado: ' . $info[1] . ' px'); ?></td></tr>
            <tr><th scope="row">Banner celular</th><td><?php rc_apuracao_image_field("{$n}[mobile]", $g('mobile'), 'Tamanho recomendado: ' . $info[2] . ' px'); ?></td></tr>
            <tr><th scope="row">Link ao clicar</th><td><input type="url" class="regular-text" name="<?php echo esc_attr("{$n}[link]"); ?>" value="<?php echo esc_attr($g('link')); ?>" placeholder="https://site-do-anunciante.com.br"></td></tr>
            <tr><th scope="row">Nome do anunciante</th><td><input type="text" class="regular-text" name="<?php echo esc_attr("{$n}[alt]"); ?>" value="<?php echo esc_attr($g('alt')); ?>"><p class="description">Texto alternativo da imagem (acessibilidade).</p></td></tr>
        </table>
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
        <h1>Apuração Eleições 2026</h1>

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
            <h2>Anúncios (carrossel)</h2>
            <p>Cada espaço pode ter vários banners, que se revezam sozinhos em carrossel. Cada banner tem uma imagem para computador e outra para celular; se mandar só uma, ela é usada nos dois. Espaço sem banner não aparece no site.
               <a href="<?php echo esc_url(rc_apuracao_url(array('anuncios' => 'exemplo'))); ?>" target="_blank" rel="noopener">Ver onde ficam os espaços</a></p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="rc_ad_int">Trocar banner a cada</label></th>
                    <td><input id="rc_ad_int" type="number" min="2" max="60" class="small-text" name="<?php echo esc_attr("{$an}[intervalo]"); ?>" value="<?php echo esc_attr(isset($ads['intervalo']) ? $ads['intervalo'] : 6); ?>"> segundos</td>
                </tr>
                <?php $sell = !isset($ads['anuncie']) || $ads['anuncie']; ?>
                <tr>
                    <th scope="row">Espaço sem banner</th>
                    <td>
                        <label><input type="checkbox" name="<?php echo esc_attr("{$an}[anuncie]"); ?>" value="1" <?php checked($sell); ?>> Mostrar a caixa <strong>“Anuncie aqui”</strong> nos espaços que ainda não têm banner</label>
                        <p class="description">Desmarcado, o espaço sem banner some do site.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rc_sell_txt">Texto do “Anuncie aqui”</label></th>
                    <td><input id="rc_sell_txt" type="text" class="large-text" name="<?php echo esc_attr("{$an}[anuncie_texto]"); ?>" value="<?php echo esc_attr(isset($ads['anuncie_texto']) ? $ads['anuncie_texto'] : ''); ?>" placeholder="Sua marca neste espaço durante a apuração">
                        <p class="description">Ex.: “Fale conosco: (86) 99999-9999”.</p></td>
                </tr>
                <tr>
                    <th scope="row"><label for="rc_sell_link">Link do “Anuncie aqui”</label></th>
                    <td><input id="rc_sell_link" type="text" class="regular-text" name="<?php echo esc_attr("{$an}[anuncie_link]"); ?>" value="<?php echo esc_attr(isset($ads['anuncie_link']) ? $ads['anuncie_link'] : ''); ?>" placeholder="https://wa.me/5586999999999">
                        <p class="description">Opcional: WhatsApp (https://wa.me/55…), e-mail (mailto:…) ou página de contato.</p></td>
                </tr>
            </table>
            <?php foreach (rc_apuracao_ad_slots() as $slot => $info) :
                $items = rc_apuracao_ad_items($ads, $slot);
                if (!$items) $items = array(array()); ?>
                <h3 style="margin-bottom:0"><?php echo esc_html($info[0]); ?></h3>
                <div class="rc-list" data-slot="<?php echo esc_attr($slot); ?>">
                    <?php foreach ($items as $i => $item) rc_apuracao_ad_card($slot, $i, $item, $info); ?>
                </div>
                <script type="text/template" class="rc-tpl" data-slot="<?php echo esc_attr($slot); ?>"><?php rc_apuracao_ad_card($slot, '__i__', array(), $info); ?></script>
                <p><button type="button" class="button rc-add" data-slot="<?php echo esc_attr($slot); ?>">+ Adicionar banner neste espaço</button></p>
            <?php endforeach; ?>

            <?php $qrs = rc_apuracao_qr_items($ads); ?>
            <h2>QR codes</h2>
            <p>Até 2 quadradinhos ao lado do anúncio do topo (no celular, logo abaixo dele). QR code sem imagem não aparece.</p>
            <?php for ($k = 0; $k < 2; $k++) :
                $q = isset($qrs[$k]) ? $qrs[$k] : array();
                $gq = function ($f) use ($q) { return isset($q[$f]) ? $q[$f] : ''; };
                $qn = "{$an}[qr][{$k}]"; ?>
                <h3 style="margin-bottom:0">QR code <?php echo $k + 1; ?></h3>
                <table class="form-table" role="presentation">
                    <tr><th scope="row">Imagem do QR code</th><td><?php rc_apuracao_image_field("{$qn}[imagem]", $gq('imagem'), 'Imagem quadrada, de preferência 300 × 300 px'); ?></td></tr>
                    <tr><th scope="row">Título</th><td><input type="text" class="regular-text" name="<?php echo esc_attr("{$qn}[titulo]"); ?>" value="<?php echo esc_attr($gq('titulo')); ?>" placeholder="Aponte a câmera"></td></tr>
                    <tr><th scope="row">Texto</th><td><input type="text" class="large-text" name="<?php echo esc_attr("{$qn}[texto]"); ?>" value="<?php echo esc_attr($gq('texto')); ?>" placeholder="Ex.: Acompanhe ao vivo no nosso site"></td></tr>
                    <tr><th scope="row">Link ao clicar</th><td><input type="url" class="regular-text" name="<?php echo esc_attr("{$qn}[link]"); ?>" value="<?php echo esc_attr($gq('link')); ?>" placeholder="https://"><p class="description">Opcional: quem estiver no celular pode tocar no QR code em vez de escanear.</p></td></tr>
                </table>
            <?php endfor; ?>

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
            // carrossel: numera os banners, adiciona um novo a partir do modelo e remove
            function numerar() {
                $('.rc-list').each(function () {
                    $(this).find('.rc-card-title').each(function (k) { $(this).text('Banner ' + (k + 1)); });
                });
            }
            numerar();
            $(document).on('click', '.rc-add', function (e) {
                e.preventDefault();
                var slot = $(this).data('slot');
                var html = $('.rc-tpl[data-slot="' + slot + '"]').html().replace(/__i__/g, 'n' + Date.now());
                $('.rc-list[data-slot="' + slot + '"]').append(html);
                numerar();
            });
            $(document).on('click', '.rc-card-del', function (e) {
                e.preventDefault();
                $(this).closest('.rc-card').remove();
                numerar();
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
