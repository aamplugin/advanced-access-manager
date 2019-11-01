#!/bin/bash
./sonarqube/bin/sonar-scanner \
  -Dsonar.projectKey=aam \
  -Dsonar.sources=. \
  -Dsonar.host.url=http://localhost:9000 \
  -Dsonar.login=80d934516a0c644fe8f1066a6e4a615d433020ef