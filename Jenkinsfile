pipeline {
    agent {
        docker {
            image 'php:8.2'
            args '-v /var/run/docker.sock:/var/run/docker.sock'
        }
    }

    environment {
        SONARQUBE_ENV = credentials('sonar-token') // Si tu veux sécuriser le token
        SONARQUBE_SERVER = 'sonarqube'
    }

    stages {

        stage('Checkout') {
            steps {
                git branch: 'main',
                    url: 'https://github.com/ton-utilisateur/ton-projet.git'
            }
        }

        stage('Install Composer Dependencies') {
            steps {
                sh '''
                apt-get update && apt-get install -y unzip git libzip-dev
                docker-php-ext-install zip pdo pdo_mysql
                curl -sS https://getcomposer.org/installer | php
                php composer.phar install --no-interaction --prefer-dist
                '''
            }
        }

        stage('Run Tests') {
            steps {
                sh '''
                ./vendor/bin/phpunit --coverage-clover var/coverage/phpunit.xml
                '''
            }
            post {
                always {
                    junit 'var/log/phpunit.log'
                }
            }
        }

        stage('SonarQube Analysis') {
            steps {
                withSonarQubeEnv("${SONARQUBE_SERVER}") {
                    sh '''
                    sonar-scanner \
                      -Dsonar.projectKey=patesansfoyer \
                      -Dsonar.sources=src \
                      -Dsonar.exclusions=var/**,vendor/**,public/bundles/** \
                      -Dsonar.php.coverage.reportPaths=var/coverage/phpunit.xml \
                      -Dsonar.php.tests.reportPath=var/log/phpunit.log
                    '''
                }
            }
        }

        stage("Quality Gate") {
            steps {
                timeout(time: 2, unit: 'MINUTES') {
                    waitForQualityGate abortPipeline: true
                }
            }
        }
    }
}