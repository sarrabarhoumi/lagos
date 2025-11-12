pipeline {
    agent any

    environment {
        DOCKER_IMAGE      = 'lagos_app'
        DOCKER_REGISTRY   = 'sarra63578'
        DOCKER_CREDENTIALS = 'dockerhub-credentials'  // ton PAT Docker Hub
        GIT_CREDENTIALS    = '32f29e88-3a7a-4d61-8115-d0d581a27f95' // SSH GitHub
    }

    triggers {
        pollSCM('H/5 * * * *') // Vérifie le repo toutes les 5 minutes
    }

    stages {
        stage('Checkout') {
            steps {
                echo "📥 Récupération du code depuis GitHub..."
                checkout([$class: 'GitSCM',
                    branches: [[name: 'main']],
                    doGenerateSubmoduleConfigurations: false,
                    extensions: [[$class: 'CleanBeforeCheckout']],
                    userRemoteConfigs: [[
                        url: 'git@github.com:sarrabarhoumi/lagos.git',
                        credentialsId: "${GIT_CREDENTIALS}"
                    ]]
                ])
            }
        }

        stage('Build Docker') {
            steps {
                echo "🏗️ Construction de l'image Docker..."
                sh """
                    docker rmi -f $DOCKER_REGISTRY/$DOCKER_IMAGE:latest || true
                    docker build -t $DOCKER_REGISTRY/$DOCKER_IMAGE:latest .
                """
            }
        }

        stage('Scan avec Trivy') {
            steps {
                echo "🔍 Scan de sécurité avec Trivy..."
                script {
                    def trivyExists = sh(script: "command -v trivy || true", returnStdout: true).trim()
                    if (trivyExists) {
                        sh "trivy image --exit-code 1 --severity CRITICAL,HIGH $DOCKER_REGISTRY/$DOCKER_IMAGE:latest || echo 'Scan terminé avec alertes'"
                    } else {
                        echo "⚠️ Trivy n'est pas installé, scan ignoré"
                    }
                }
            }
        }

        stage('Push Docker Hub') {
            steps {
                echo "☁️ Poussée de l'image sur Docker Hub..."
                withCredentials([usernamePassword(
                    credentialsId: "${DOCKER_CREDENTIALS}", 
                    usernameVariable: 'DOCKER_USER', 
                    passwordVariable: 'DOCKER_PASS'
                )]) {
                    sh """
                        echo "$DOCKER_PASS" | docker login -u "$DOCKER_USER" --password-stdin
                        docker push $DOCKER_REGISTRY/$DOCKER_IMAGE:latest
                        docker logout
                    """
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
