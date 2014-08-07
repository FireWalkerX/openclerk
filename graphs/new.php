<?php

/**
 * How the new graph render code works:
 * {@link render_graph_new()} generates the HTML on the page necessary to render the graph;
 * which uses graphs.js
 * which uses AJAX to load data, and timeouts to reload graphs
 * which then call the site API to load graph data
 * and then renders the graph using Google Graphs API (or whatever graphing framework we're using)
 */

/**
 * Render the HTML on the page necessary for rendering a graph to the user.
 * @param $graph = array(
 *		'graph_type' => $id,
 *		'width' => 8,
 *		'height' => 4,
 *		'page_order' => 0,
 *		'days' => $days,
 *		'id' => 0,
 *		'arg0_resolved' => $name,
 *		'delta' => $delta,
 *		'public' => true,
 *		'no_technicals' => true,
 *	);
 * @param $include_user_hash if true, include user_id and user_hash in the graph data, necessary for
 *				graphs that require user authentication; default is false
 */
function render_graph_new($graph, $include_user_hash = false) {
	global $_rendered_graph_contents;
	if (!$_rendered_graph_contents) {
		?>
		<div id="graph_contents_template" style="display:none;">
			<div class="graph_headings">
				<h1 class="h1"></h1>
				<h2 class="h2"></h2>
				<h2 class="graph_title">
					<a href=""></a>
				</h2>
				<span class="subheading"></span>
				<span class="last-updated"></span>
			</div>
			<div class="graph-target"><span class="status_loading">Loading...</span></div>
			<div class="admin-stats-wrapper hide-admin"><span class="admin-stats render_time"></span></div>
		</div>
		<div id="graph_table_template" class="overflow_wrapper" style="display:none;">
			<table class="standard graph_table">
			</table>
		</div>
		<?php
	}

	$graph_id = "graph_" . rand(0,0xffff);
	$graph['target'] = $graph_id;

	$graph['graphWidth'] = get_site_config('default_graph_width') * $graph['width'];
	$graph['computedWidth'] = $graph['graphWidth'];
	$graph['graphHeight'] = get_site_config('default_graph_height') * $graph['height'];
	$graph['computedHeight'] = $graph['graphHeight'] + 30;

	// if we are logged in, also provide the user ID and computed user hash, to verify that we can
	// correctly access this graph (also means that we don't have to initialise sessions on the API)
	if ($include_user_hash && user_logged_in()) {
		$graph['user_id'] = user_id();
		$graph['user_hash'] = compute_user_graph_hash(get_user(user_id()));
	}

	// 'overflow: hidden;' is to fix a Chrome rendering bug
	?>
		<div id="<?php echo htmlspecialchars($graph_id); ?>" class="graph" style="overflow: hidden;"></div>
		<script type="text/javascript">
			Graphs.render(<?php echo json_encode($graph); ?>);
		</script>
	<?php
}

$_rendered_graph_contents = false;

class NoGraphRendererException extends GraphException { }
class RenderGraphException extends GraphException { }

function compute_user_graph_hash($user) {
	return md5(get_site_config('user_graph_hash_salt') . ":" . $user['id'] . ":" . $user['last_login']);
}
