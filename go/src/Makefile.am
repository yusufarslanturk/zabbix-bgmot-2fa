## Process this file with automake to produce Makefile.in

## Process this file with automake to produce Makefile.in
BUILD_TIME=`date +%H:%M:%S`
BUILD_DATE=`date +"%b %_d %Y"`
GOOS=`go env GOOS`
GOARCH=`go env GOARCH`
PKG=zabbix/pkg/version

GOLDFLAGS = -X ${PKG}.titleMessage=zabbix_agentd
GOLDFLAGS += -X '${PKG}.compileDate=${BUILD_DATE}'
GOLDFLAGS += -X ${PKG}.compileTime=${BUILD_TIME}
GOLDFLAGS += -X ${PKG}.compileOs=${GOOS}
GOLDFLAGS += -X ${PKG}.compileArch=${GOARCH}
GOLDFLAGS += -X ${PKG}.compileMode=daemon
GOLDFLAGS += -X main.confDefault=${AGENT2_CONFIG_FILE}

all: build

build:
	CGO_CFLAGS="${CGO_CFLAGS}" CGO_LDFLAGS="${CGO_LDFLAGS}" go build -ldflags="${GOLDFLAGS}" -mod vendor -o ../bin ./...

clean:
	go clean -mod vendor ./...
	rm -rf ../bin/zabbix_agent2 ../bin/mock_server

install:
	CGO_CFLAGS="${CGO_CFLAGS}" CGO_LDFLAGS="${CGO_LDFLAGS}" GOPATH="${GOPATH}" \
		go install -mod vendor -ldflags="${GOLDFLAGS}" ./...
	test -f "$(DESTDIR)@AGENT2_CONFIG_FILE@" || cp "../../conf/zabbix_agent2.conf" "$(DESTDIR)@AGENT2_CONFIG_FILE@"

check:
	CGO_CFLAGS="${CGO_CFLAGS}" CGO_LDFLAGS="${CGO_LDFLAGS}" go test -mod vendor ./...

