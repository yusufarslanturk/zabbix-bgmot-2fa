package web

import (
	"bytes"
	"fmt"
	"net"
	"net/http"
	"net/http/httputil"
	"net/url"
	"time"

	"zabbix.com/internal/agent"
	"zabbix.com/pkg/conf"
	"zabbix.com/pkg/plugin"
	"zabbix.com/pkg/version"
)

type Options struct {
	Timeout int `conf:"optional,range=1:30"`
}

type Plugin struct {
	plugin.Base
	options Options
}

var impl Plugin

func (p *Plugin) Configure(global *plugin.GlobalOptions, options interface{}) {
	if err := conf.Unmarshal(options, &p.options); err != nil {
		p.Warningf("cannot unmarshal configuration options: %s", err)
	}
	if p.options.Timeout == 0 {
		p.options.Timeout = global.Timeout
	}
}

func (p *Plugin) Validate(options interface{}) error {
	var o Options
	return conf.Unmarshal(options, &o)
}

func disableRedirect(req *http.Request, via []*http.Request) error {
	return http.ErrUseLastResponse
}

func (p *Plugin) Export(key string, params []string, ctx plugin.ContextProvider) (interface{}, error) {

	if len(params) > 3 {
		return nil, fmt.Errorf("Too many parameters.")
	}

	if len(params) == 0 || params[0] == "" {
		return nil, fmt.Errorf("Invalid first parameter.")
	}

	u, err := url.Parse(params[0])
	if err != nil {
		return nil, fmt.Errorf("Cannot parse url %s", err)
	}

	if u.Scheme == "" || u.Opaque != "" {
		params[0] = "http://" + params[0]
	}

	if len(params) > 2 && params[2] != "" {
		params[0] += ":" + params[2]
	}

	if len(params) > 1 && params[1] != "" {
		if params[1][0] != '/' {
			params[0] += "/"
		}

		params[0] += params[1]
	}

	req, err := http.NewRequest("GET", params[0], nil)
	if err != nil {
		return nil, fmt.Errorf("Cannot create new request: %s", err)
	}

	req.Header = map[string][]string{
		"User-Agent": {"Zabbix " + version.Long()},
	}

	client := &http.Client{
		Transport: &http.Transport{
			Proxy:             http.ProxyFromEnvironment,
			DisableKeepAlives: true,
			DialContext: (&net.Dialer{
				LocalAddr: &net.TCPAddr{IP: net.ParseIP(agent.Options.SourceIP), Port: 0},
			}).DialContext,
		},
		Timeout:       time.Duration(p.options.Timeout) * time.Second,
		CheckRedirect: disableRedirect,
	}

	resp, err := client.Do(req)
	if err != nil {
		return nil, fmt.Errorf("Cannot get content of web page: %s", err)
	}

	defer resp.Body.Close()

	b, err := httputil.DumpResponse(resp, true)
	if err != nil {
		return nil, fmt.Errorf("Cannot get content of web page: %s", err)
	}

	return string(bytes.TrimRight(b, "\r\n")), nil

}

func init() {
	plugin.RegisterMetrics(&impl, "Web",
		"web.page.get", "Get content of a web page.")
}
