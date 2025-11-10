pipeline {
    agent any

    environment {
        DOCKER_IMAGE = 'lagos_app'
        DOCKER_REGISTRY = 'sarra63578'
    }

    stages {
        stage('Build') {
            steps {
                echo "🏗️ Construction de l'image Docker..."
                sh 'docker build -t $IMAGE_NAME:latest .'
            }
        }

        stage('Scan avec Trivy') {
            steps {
                echo "🔍 Scan de sécurité avec Trivy..."
                sh '''
                trivy image --exit-code 1 --severity CRITICAL,HIGH $IMAGE_NAME:latest || echo "Scan terminé avec alertes"
                '''
            }
        }

        stage('Push vers Docker Hub') {
            steps {
                echo "☁️ Poussée de l'image sur Docker Hub..."
                sh '''
                echo "$DOCKER_HUB_CREDENTIALS_PSW" | docker login -u "$DOCKER_HUB_CREDENTIALS_USR" --password-stdin
                docker push $IMAGE_NAME:latest
                '''
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