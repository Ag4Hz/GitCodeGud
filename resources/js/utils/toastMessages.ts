const successMessages: {[key: string]: string[]} = {
    en: [
        "All done, like a boss! 😎",
        "Success! You're on fire! 🔥",
        "Boom! Task completed! 💥",
        "Nailed it! 🎯",
        "Mission accomplished! 🚀",
    ],
    hu: [
        "Kész, mint a rakéta! 🚀",
        "Siker! Te vagy a legjobb! 🌟",
        "Bumm! Feladat teljesítve! 💥",
        "Elintézve! 🎯",
        "Küldetés teljesítve! 🚀",
    ],
    de: [
        "Geschafft, wie ein Boss! 😎",
        "Erfolg! Du bist der Hammer! 🔥",
        "Bumm! Aufgabe erledigt! 💥",
        "Volltreffer! 🎯",
        "Mission erfüllt! 🚀",
    ],
}

const errorMessages: {[key: string]: string[]} = {
    en: [
        "Oops! Something went wrong. 😬",
        "Uh-oh! Try again later. ⏳",
        "Yikes! An error occurred. ⚠️",
        "Well, that didn't work. 🤷‍♂️",
        "Error 404: Fun not found. 🚫",
    ],
    hu: [
        "Hoppá! Valami hiba történt. 😬",
        "Jaj! Próbáld újra később. ⏳",
        "Hűha! Hiba történt. ⚠️",
        "Nos, ez nem sikerült. 🤷‍♂️",
        "Hiba 404: Szórakozás nem található. 🚫",
    ],
    de: [
        "Ups! Etwas ist schief gelaufen. 😬",
        "Oh oh! Versuche es später noch einmal. ⏳",
        "Hoppla! Ein Fehler ist aufgetreten. ⚠️",
        "Nun, das hat nicht funktioniert. 🤷‍♂️",
        "Fehler 404: Spaß nicht gefunden. 🚫",
    ],
}

export function getRandomSuccessMessage(locale: string): string {
    const messages = successMessages[locale] || successMessages["en"]
    return messages[Math.floor(Math.random() * messages.length)]
}

export function getRandomErrorMessage(locale: string): string {
    const messages = errorMessages[locale] || errorMessages["en"]
    return messages[Math.floor(Math.random() * messages.length)]
}
