# Copyright 2019 Pressinfra Authors. All rights reserved.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

ifndef __KUBEBUILDER_MAKEFILE__
__KUBEBUILDER_MAKEFILE__ := included

# ====================================================================================
# Options

KUBEBUILDER_VERSION ?= 3.1.0
KUBEBUILDER := $(TOOLS_HOST_DIR)/kubebuilder-$(KUBEBUILDER_VERSION)

K8S_VERSION := 1.19.2

CRD_DIR ?= config/crds
API_DIR ?= pkg/apis
RBAC_DIR ?= config/rbac

BOILERPLATE_FILE ?= ./hack/boilerplate.go.txt

GEN_CRD_OPTIONS ?= crd:trivialVersions=true
GEN_RBAC_OPTIONS ?= rbac:roleName=manager-role
GEN_WEBHOOK_OPTIONS ?=
GEN_OBJECT_OPTIONS ?= object:headerFile=$(BOILERPLATE_FILE)
GEN_OUTPUTS_OPTIONS ?= output:crd:artifacts:config=$(CRD_DIR) output:rbac:artifacts:config=$(RBAC_DIR)

# these are use by the kubebuilder test harness

TEST_ASSET_KUBE_APISERVER := $(KUBEBUILDER)/kube-apiserver
TEST_ASSET_ETCD := $(KUBEBUILDER)/etcd
TEST_ASSET_KUBECTL := $(KUBEBUILDER)/kubectl
export TEST_ASSET_KUBE_APISERVER TEST_ASSET_ETCD TEST_ASSET_KUBECTL

# ====================================================================================
# Setup environment

include $(COMMON_SELF_DIR)/golang.mk

# ====================================================================================
# tools

# kubebuilder download and install
$(KUBEBUILDER):
	@echo ${TIME} ${BLUE}[TOOL]${CNone} installing kubebuilder $(KUBEBUILDER_VERSION)

	@mkdir -p $(KUBEBUILDER)
	@mkdir -p $(TOOLS_HOST_DIR)/tmp || $(FAIL)

	@# kubebuilder
	@curl -sL -o $(KUBEBUILDER)/kubebuilder https://github.com/kubernetes-sigs/kubebuilder/releases/download/v$(KUBEBUILDER_VERSION)/kubebuilder_$(GOHOSTOS)_$(GOHOSTARCH) || $(FAIL)
	@chmod +x $(KUBEBUILDER)/kubebuilder || $(FAIL)

	@# kubebuilder-tools
	@curl -fsSL https://storage.googleapis.com/kubebuilder-tools/kubebuilder-tools-$(K8S_VERSION)-$(GOHOSTOS)-$(GOHOSTARCH).tar.gz | tar -xz -C $(TOOLS_HOST_DIR)/tmp || $(FAIL)
	@mv $(TOOLS_HOST_DIR)/tmp/kubebuilder/bin/* $(KUBEBUILDER) || $(FAIL)
	@rm -rf $(TOOLS_HOST_DIR)/tmp

	@$(OK) installing kubebuilder $(KUBEBUILDER_VERSION)


CONTROLLER_GEN_VERSION ?= 0.6.1
CONTROLLER_GEN_URL ?= sigs.k8s.io/controller-tools/cmd/controller-gen
$(eval $(call tool.go.install,controller-gen,v$(CONTROLLER_GEN_VERSION),$(CONTROLLER_GEN_URL)))

# ====================================================================================
# Kubebuilder Targets

$(eval $(call common.target,kubebuilder.manifests))

# Generate manifests e.g. CRD, RBAC etc.
.do.kubebuilder.manifests: $(CONTROLLER_GEN)
	@$(INFO) Generating Kubebuilder manifests
	@# first delete the CRD_DIR, to remove the CRDs of types that no longer exist

	@$(CONTROLLER_GEN) paths="./pkg/..." $(GEN_CRD_OPTIONS) $(GEN_RBAC_OPTIONS) $(GEN_WEBHOOK_OPTIONS) $(GEN_OBJECT_OPTIONS) $(GEN_OUTPUTS_OPTIONS)

	@$(OK) Generating Kubebuilder manifests

.PHONY: .do.kubebuilder.manifests
.kubebuilder.manifests.run: .do.kubebuilder.manifests

# ====================================================================================
# Common Targets

build.tools: $(KUBEBUILDER)
.test.init: $(KUBEBUILDER)
go.test.unit: $(KUBEBUILDER)

# ====================================================================================
# Special Targets

define KUBEBULDERV2_HELPTEXT
Kubebuilder Targets:
    kubebuilder.manifests   Generates Kubernetes custom resources manifests (e.g. CRDs RBACs, ...)

endef
export KUBEBULDERV2_HELPTEXT

.kubebuilder.help:
	@echo "$$KUBEBULDERV2_HELPTEXT"

.help: .kubebuilder.help
go.generate: kubebuilder.manifests

.PHONY: .kubebuilder.help kubebuilder.manifests

endif # __KUBEBUILDER_MAKEFILE__
