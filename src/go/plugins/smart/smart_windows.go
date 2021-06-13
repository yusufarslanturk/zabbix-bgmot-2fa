<<<<<<< HEAD
// +build  windows

=======
<<<<<<< HEAD:ui/app/partials/js/common.filter.item.js.php
<?php declare(strict_types=1);
=======
// +build  windows

>>>>>>> 5.2.6-bg:src/go/plugins/smart/smart_windows.go
>>>>>>> 5.2.6-bg
/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

package smart

import (
	"fmt"
	"time"

	"zabbix.com/pkg/zbxcmd"
)

<<<<<<< HEAD
=======
<<<<<<< HEAD:ui/app/partials/js/common.filter.item.js.php
/**
 * @var CPartial $this
 */
?>

<script type="text/x-jquery-tmpl" id="filter-tag-row-tmpl">
	<?= CTagFilterFieldHelper::getTemplate(); ?>
</script>

<script type="text/javascript">
	(function($) {
		$(function() {
			$('#filter-tags')
				.dynamicRows({ template: '#filter-tag-row-tmpl' })
				.on('afteradd.dynamicRows', function() {
					var rows = this.querySelectorAll('.form_row');
					new CTagFilterItem(rows[rows.length - 1]);
				});

			// Init existing fields once loaded.
			document.querySelectorAll('#filter-tags .form_row').forEach(row => {
				new CTagFilterItem(row);
			});
		});
	})(jQuery);
</script>
=======
>>>>>>> 5.2.6-bg
func (p *Plugin) executeSmartctl(args string, strict bool) ([]byte, error) {
	path := "smartctl"

	if p.options.Path != "" {
		path = p.options.Path
	}

	var out string

	var err error

	if strict {
		out, err = zbxcmd.ExecuteStrict(fmt.Sprintf("%s %s", path, args), time.Second*time.Duration(p.options.Timeout), "")
	} else {
		out, err = zbxcmd.Execute(fmt.Sprintf("%s %s", path, args), time.Second*time.Duration(p.options.Timeout), "")
	}

	return []byte(out), err
}
<<<<<<< HEAD
=======
>>>>>>> 5.2.6-bg:src/go/plugins/smart/smart_windows.go
>>>>>>> 5.2.6-bg
