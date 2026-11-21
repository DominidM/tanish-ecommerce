<?php
/**
 * Plugin Name: TANISH - Runner de Action Scheduler (desarrollo)
 * Description: En local el loopback de WordPress no alcanza el sitio, así que el runner de Action Scheduler se dispara con la petición. En producción (sin ALTERNATE_WP_CRON) no hace nada.
 */

defined('ABSPATH') || exit;

add_action(
	'wp_loaded',
	function (): void {
		// Solo en entornos donde el loopback no funciona (dev con ALTERNATE_WP_CRON).
		if (!defined('ALTERNATE_WP_CRON') || !ALTERNATE_WP_CRON) {
			return;
		}

		if (!function_exists('as_next_scheduled_action') || wp_doing_cron()) {
			return;
		}

		// Como máximo una ejecución por minuto.
		if (get_transient('tanish_as_runner_lock')) {
			return;
		}

		set_transient('tanish_as_runner_lock', 1, MINUTE_IN_SECONDS);

		// Procesa los lotes de Action Scheduler sin depender del loopback.
		do_action('action_scheduler_run_queue', 'WP Cron');
	},
	20
);
