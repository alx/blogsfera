#!/usr/bin/env python
# -*- coding: utf-8 -*-
#
# Copyright (C) 2006 Omry Yadan
# All rights reserved.

from setuptools import setup, find_packages

PACKAGE = 'FireStats'
VERSION = '1.0.0'

setup(
    name=PACKAGE, version=VERSION,
    description='Integration with the FireStats web statistics system',
    author="Omry Yadan", author_email="omry@yadan.net",
    license='See on FireStats site', url='http://firestats.cc',
    packages=find_packages(exclude=['ez_setup', '*.tests*']),
    package_data={
    },
    entry_points = {
        'trac.plugins': [
            'firestats.hitlogger = firestats.hitlogger'
        ]
    }
)

