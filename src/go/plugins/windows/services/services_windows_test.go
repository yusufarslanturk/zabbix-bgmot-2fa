package services

import (
	"fmt"
	"testing"
)

func TestDiscovery(t *testing.T) {
	var p Plugin

	ret, err := p.exportServiceDiscovery([]string{})
	fmt.Println(ret, err)

	t.Fail()

}
