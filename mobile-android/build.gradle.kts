plugins {
    // Versions relevées depuis 8.5.0/1.9.24 : le compilateur Kotlin 1.9.x
    // plante ("Internal compiler error... 26.0.2") sur les JDK récents (25/26)
    // installés sur cette machine, aucun JDK plus ancien n'étant disponible.
    id("com.android.application") version "8.7.3" apply false
    id("org.jetbrains.kotlin.android") version "2.0.21" apply false
}
