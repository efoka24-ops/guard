plugins {
    id("com.android.application")
    id("org.jetbrains.kotlin.android")
}

android {
    namespace = "africa.trugroup.guard"
    compileSdk = 34

    defaultConfig {
        applicationId = "africa.trugroup.guard"
        minSdk = 26 // Android 8.0 — Principe I (Offline-First) / contrainte SFD
        targetSdk = 34
        versionCode = 1
        versionName = "0.1.0"
    }

    buildTypes {
        release {
            isMinifyEnabled = true
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = "17"
    }
}

dependencies {
    implementation("androidx.core:core-ktx:1.13.1")
    implementation("androidx.work:work-runtime-ktx:2.9.0") // synchronisation différée offline-first
    implementation("androidx.room:room-runtime:2.6.1")      // base locale signatures + file d'envoi
    implementation("org.tensorflow:tensorflow-lite:2.16.1") // niveau 3 (incrément futur, cf. research.md §3)
    testImplementation("junit:junit:4.13.2")
    androidTestImplementation("androidx.test.ext:junit:1.2.1")
}
