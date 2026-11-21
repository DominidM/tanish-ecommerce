<?php
/**
 * Plugin Name: TANISH - Cron interno (desarrollo)
 * Description: En local el loopback de WordPress no alcanza el sitio. Con DISABLE_WP_CRON activo, ejecuta los eventos vencidos y el runner de Action Scheduler dentro de la propia petición, sin redirecciones.
 */

defined('ABSPATH') || exit;

add_action(
	'wp_loaded',
	function (): void {
		// Solo en desarrollo (docker-compose define DISABLE_WP_CRON).
		// En producción el cron real del servidor se encarga.
		if (!defined('DISABLE_WP_CRON') || !DISABLE_WP_CRON) {
			return;
		}

		if (wp_doing_cron() || get_transient('tanish_cron_lock')) {
			return;
		}

		set_transient('tanish_cron_lock', 1, MINUTE_IN_SECONDS);

		// 1) Eventos de WP-Cron vencidos, sin loopback.
		$crons = _get_cron_array();

		if (is_array($crons)) {
			$now = time();

			foreach ($crons as $timestamp => $hooks) {
				if ($timestamp > $now || !is_array($hooks)) {
					continue;
				}

				foreach ($hooks as $hook => $events) {
					if (!is_array($events)) {
						continue;
					}

					foreach ($events as $event) {
						$args = isset($event['args']) && is_array($event['args']) ? $event['args'] : array();

						if (empty($event['schedule'])) {
							wp_unschedule_event($timestamp, $hook, $args);
						} else {
							wp_reschedule_event($timestamp, $event['schedule'], $hook, $args);
						}

						try {
							do_action_ref_array($hook, $args);
						} catch (Throwable $error) {
							error_log('[TANISH CRON] ' . $hook . ': ' . $error->getMessage());
						}
					}
				}
			}
		}

		// 2) Runner de Action Scheduler (WooCommerce).
		if (function_exists('as_next_scheduled_action')) {
			do_action('action_scheduler_run_queue', 'WP Cron');
		}
	},
	20
);
