package web

import (
	"errors"
	"fmt"
	"io/ioutil"
	"net"
	"net/http"
	"time"

	"zabbix.com/internal/agent"
	"zabbix.com/pkg/plugin"
)

type Plugin struct {
	plugin.Base
}

var impl Plugin

func disableRedirect(req *http.Request, via []*http.Request) error {
	return errors.New("redirects are disabled")
}

func (p *Plugin) Export(key string, params []string, ctx plugin.ContextProvider) (interface{}, error) {

	if len(params) > 3 {
		return nil, fmt.Errorf("Too many parameters.")
	}

	if len(params) == 0 || params[0] == "" {
		return nil, fmt.Errorf("Invalid first parameter.")
	}

	req, err := http.NewRequest("GET", params[0], nil)
	if err != nil {
		return nil, fmt.Errorf("Cannot create new request: %s", err)
	}

	req.Header = map[string][]string{
		"User-Agent": {"Zabbix"},
	}

	client := &http.Client{
		Transport: &http.Transport{
			Proxy:             http.ProxyFromEnvironment,
			DisableKeepAlives: true,
			DialContext: (&net.Dialer{
				LocalAddr: &net.TCPAddr{IP: net.ParseIP(agent.Options.SourceIP), Port: 0},
			}).DialContext,
		},
		Timeout:       time.Duration(30) * time.Second,
		CheckRedirect: disableRedirect,
	}

	resp, err := client.Do(req)
	if err != nil {
		return nil, fmt.Errorf("Cannot get content of web page: %s", err)
	}

	defer resp.Body.Close()

	body, err := ioutil.ReadAll(resp.Body)
	if err != nil {
		return nil, fmt.Errorf("Cannot get content of web page: %s", err)
	}

	return string(body), nil

}

func init() {
	plugin.RegisterMetrics(&impl, "Web",
		"web.page.get", "Get content of a web page.")
}
