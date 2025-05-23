<?php

namespace ionos\essentials\dashboard\blocks\next_best_actions;

use const ionos\essentials\PLUGIN_DIR;

\add_action('init', function () {
  \register_block_type(
    PLUGIN_DIR . '/build/dashboard/blocks/next-best-actions',
    [
      'render_callback' => 'ionos\essentials\dashboard\blocks\next_best_actions\render_callback',
    ]
  );
});

function render_callback()
{
  require_once __DIR__ . '/class-nba.php';
  $actions = NBA::get_actions();
  if (empty($actions)) {
    return;
  }

  $template = '
  <div id="ionos-dashboard__essentials_nba" class="wp-block-group alignwide">
      <div class="wp-block-group is-layout-flow" style="margin-top:0px;margin-bottom:15px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
          <h3 class="wp-block-heading">%s</h3>
          <p>%s</p>
      </div>
      <div class="wp-block-columns is-layout-flex wp-container-core-columns-is-layout-1 wp-block-columns-is-layout-flex" id="actions">
          %s
      </div>
  </div>';

  $header      = \esc_html__("Unlock Your Website's Potential", 'ionos-essentials');
  $description = \esc_html__(
    'Your website is live, but your journey is just beginning. Explore the recommended next actions to drive growth, improve performance, and achieve your online goals.',
    'ionos-essentials'
  );
  $body = '';
  foreach ($actions as $action) {
    if (! $action->active) {
      continue;
    }

    $target = false === strpos(\esc_url($action->link), home_url()) ? '_blank' : '_top';
    if ('#' === $action->link) {
      $target = '';
    }
    $body .= '
      <div class="wp-block-column is-style-default has-background is-layout-flow action" style="border-radius:24px;background-color:#f4f7fa">
        <div class="action-content">
          <h3 class="wp-block-heading">' . \esc_html($action->title) . '</h3>
          <p>' . \esc_html($action->description) . '</p>
          <div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex">
              <div class="wp-block-button">
                  <a data-nba-id="' . $action->id . '" href="' . \esc_url(
      $action->link
    ) . '" class="wp-block-button__link wp-element-button nba-link" target="' . $target . '">' . \esc_html(
      $action->anchor
    ) . '</a>
              </div>
              <div class="wp-block-button is-style-outline is-style-outline--1">
                  <a data-nba-id="' . $action->id . '" class="wp-block-button__link wp-element-button dismiss-nba" target="_top">' . \esc_html__(
      'Dismiss',
      'ionos-essentials'
    ) . '</a>
              </div>
            </div>
          </div>
      </div>';
  }

  if (empty($body)) {
    return;
  }

  return sprintf($template, $header, $description, $body);
}

\add_action('admin_init', function () {
  if (isset($_GET['complete_nba'])) {
    require_once __DIR__ . '/class-nba.php';
    $nba_id = $_GET['complete_nba'];

    $nba = NBA::get_nba($nba_id);
    $nba->set_status('completed', true);
  }
});

\add_action('rest_api_init', function () {
  \register_rest_route('ionos/essentials/dashboard/nba/v1', '/dismiss/(?P<id>[a-zA-Z0-9-]+)', [
    'methods'  => 'POST',
    'callback' => function ($request) {
      require_once __DIR__ . '/class-nba.php';
      $params = $request->get_params();
      $nba_id = $params['id'];

      $nba = NBA::get_nba($nba_id);
      $res = $nba->set_status('dismissed', true);
      if ($res) {
        return new \WP_REST_Response([
          'status' => 'success',
          'res'    => $res,
        ], 200);
      }
      return new \WP_REST_Response([
        'status' => 'error',
      ], 500);
    },
    'permission_callback' => function () {
      return \current_user_can('manage_options');
    },
  ]);
});

\add_action('post_updated', function ($post_id, $post_after, $post_before) {
  if ('publish' !== $post_before->post_status || ('publish' !== $post_after->post_status && 'draft' !== $post_after->post_status)) {
    return;
  }

  require_once __DIR__ . '/class-nba.php';
  switch ($post_after->post_type) {
    case 'post':
      $nba = NBA::get_nba('edit-post');
      break;
    case 'page':
      $nba = NBA::get_nba('edit-page');
      break;
    default:
      return;
  }

  if ($nba) {
    $nba->set_status('completed', true);
  }
}, 10, 3);

\add_action( 'enqueue_block_editor_assets', function () {
  if ( ! isset($_GET['essentials-nba'])) {
    return;
  }
  \add_action( 'admin_footer', function () {
    echo "<script>
      const observer = new MutationObserver((mutations, obs) => {
        const iframe = document.querySelector('iframe');
        if (iframe) {
          iframe.addEventListener('load', function(event) {
            const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;
            const uploadButton = iframeDocument.querySelector('.wp-block-site-logo .components-placeholder__fieldset > button');
            if (uploadButton) {
              uploadButton.click();
            }
          });
          obs.disconnect();
        }
      });

      observer.observe(document, {
        childList: true,
        subtree: true
      });
    </script>";
  } );
} );
