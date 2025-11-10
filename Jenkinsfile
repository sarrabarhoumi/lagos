pipeline {
    agent any

    environment {
        DOCKER_IMAGE = 'lagos_app'
        DOCKER_REGISTRY = 'sarra63578'
    }

    stages {
        stage('Checkout') {
            steps {
                echo "📥 Récupération du code depuis GitHub..."
                deleteDir()
                checkout([$class: 'GitSCM',
                    branches: [[name: 'main']],
                    userRemoteConfigs: [[
                        url: 'git@github.com:sarrabarhoumi/lagos.git',
                        credentialsId: 'cda24bba-2b25-4f42-0db436c55b7b'
                    ]]
                ])
            }
        }

        stage('Build') {
            steps {
                echo "🏗️ Construction de l'image Docker..."
                sh "docker build -t $DOCKER_REGISTRY/$DOCKER_IMAGE:latest ."
            }
        }

        stage('Scan avec Trivy') {
            steps {
                echo "🔍 Scan de sécurité avec Trivy..."
                sh '''
                trivy image --exit-code 1 --severity CRITICAL,HIGH $DOCKER_REGISTRY/$DOCKER_IMAGE:latest || echo "Scan terminé avec alertes"
                '''
            }
        }

        stage('Push vers Docker Hub') {
            steps {
                echo "☁️ Poussée de l'image sur Docker Hub..."
                withCredentials([usernamePassword(credentialsId: 'dockerhub-credentials', usernameVariable: 'DOCKER_USER', passwordVariable: 'DOCKER_PASS')]) {
                    sh '''
                    echo "$DOCKER_PASS" | docker login -u "$DOCKER_USER" --password-stdin
                    docker push $DOCKER_REGISTRY/$DOCKER_IMAGE:latest
                    '''
                }
            }
        }
    }

    post {
        success {
            echo "✅ Pipeline terminé avec succès !"
        }
        failure {
            echo "❌ Une erreur est survenue dans le pipeline."
        }
    }
}
