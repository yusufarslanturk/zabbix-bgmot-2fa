/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
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

package agent

func CheckMetrics() {
	metrics := []string{
		`agent.hostname`,
		`agent.ping`,
		`agent.version`,
		`system.localtime[utc]`,
		`system.run[echo test]`,
		`web.page.get[localhost,,80]`,
		`web.page.perf[localhost,,80]`,
		`web.page.regexp[localhost,,80,OK]`,
		`vfs.file.size[c:\windows\win.ini]`,
		`vfs.file.time[c:\windows\win.ini]`,
		`vfs.file.exists[c:\windows\win.ini`,
		`vfs.file.contents[c:\windows\win.ini]`,
		`vfs.file.regexp[c:\windows\win.ini,fonts]`,
		`vfs.file.regmatch[c:\windows\win.ini]`,
		`vfs.file.md5sum[c:\windows\win.ini]`,
		`vfs.file.cksum[c:\windows\win.ini]`,
		`vfs.dir.size[c:\windows]`,
		`vfs.dir.count[c:\windows]`,
		`net.dns[,zabbix.com]`,
		`net.dns.record[,zabbix.com]`,
		`net.tcp.dns[,zabbix.com]`,
		`net.tcp.dns.query[,zabbix.com]`,
		`net.tcp.port[,80]`,
		`system.users.num`,
		`log[logfile]`,
		`log.count[logfile]`,
		`logrt[logfile]`,
		`logrt.count[logfile]`,
		`eventlog[system]`,
		`zabbix.stats[127.0.0.1,10051]`,
		`vfs.fs.size[c:,free]`,
		`vfs.fs.discovery`,
		`net.tcp.listen[80]`,
		`net.udp.listen[68]`,
		`net.if.in[MS TCP Loopback interface,bytes]`,
		`net.if.out[MS TCP Loopback interface,bytes]`,
		`net.if.total[MS TCP Loopback interface,bytes]`,
		`net.if.discovery`,
		`net.if.list`,
		`vm.memory.size[total]`,
		`proc.cpu.util[inetd]`,
		`proc.num[inetd]`,
		`proc.mem[inetd]`,
		`system.cpu.switches`,
		`system.cpu.intr`,
		`system.cpu.util[all,user,avg1]`,
		`system.cpu.load[all,avg1]`,
		`system.cpu.num[online]`,
		`system.cpu.discovery`,
		`system.uname`,
		`system.hw.chassis`,
		`system.hw.cpu`,
		`system.hw.devices`,
		`system.hw.macaddr`,
		`system.sw.arch`,
		`system.sw.os`,
		`system.sw.packages`,
		`system.swap.size[all,free]`,
		`system.swap.in[all]`,
		`system.swap.out[all]`,
		`system.uptime`,
		`system.boottime`,
		`sensor[w83781d-i2c-0-2d,temp1]`,
		`net.tcp.service[ssh,127.0.0.1,22]`,
		`net.tcp.service.perf[ssh,127.0.0.1,22]`,
		`net.udp.service[ntp,127.0.0.1,123]`,
		`net.udp.service.perf[ntp,127.0.0.1,123]`,
		`system.hostname`,
	}

	for _, metric := range metrics {
		_ = CheckMetric(metric)
	}
}
