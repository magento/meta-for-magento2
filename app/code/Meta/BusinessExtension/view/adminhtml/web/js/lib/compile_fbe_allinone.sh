#!/bin/bash
# Copyright (c) Meta Platforms, Inc. and affiliates. All Rights Reserved

NODE_PATH=. browserify fbe.js > ../fbe_allinone.js
NODE_PATH=. browserify commerce_extension.js > ../commerce_extension_allinone.js