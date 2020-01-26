package services

import (
	"fmt"
	"testing"
)

func TestDiscovery(t *testing.T) {
	var p Plugin

	ret, err := p.exportServiceInfo([]string{"Windows Search"})
	fmt.Println(ret, err)

	t.Fail()

}
