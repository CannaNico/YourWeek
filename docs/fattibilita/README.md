Studio di Fattibilità - YourWeek
=======================================
[TOC]

v0.0.1 - 05/10/25
Redatto da: Nicola Cannatà, Mirko Verzeroli, Lorenzo Gentilcore

# Sommario Esecutivo

 A project where you can plan your weekly food intake, with the addition of an automatic algorithm that reminds you to buy the products you need to complete your meal. The system also features a "pantry" section where users can virtually replicate their pantry by entering all their food items, making it easier to keep track of them.
Scopo principale dello studio: MMany people avoid following a meal plan mainly because they would be unable to stay consistent due to the organization, with this app in our opinion this will no longer be a problem, thanks to its simple interface.
Raccomandazione finale: It's feasible, because we're encouraged to do so and also because it could be useful to us too.
Investimento stimato: 500€-625€
ROI atteso: [Valore percentuale e temporale]
Rischi principali: 
    1. Any time delays.
    2. difficulties in coding and database management.
    3. maybe few customers.

# 1. Introduzione

 The purpose of this document is to assess the feasibility of the "Your Week" project, an application aimed at helping users plan and manage their weekly meals through a customizable calendar. The document evaluates the technical, operational, and economic viability of the project to determine whether it should proceed to development.
Contesto del progetto: In a fast-paced world, many individuals struggle to maintain a balanced diet due to poor planning or lack of time. "Your Week" addresses this issue by offering a digital solution for meal organization, enabling users to schedule their meals throughout the week, align them with dietary preferences, and maintain healthier eating habits. The project emerges in response to a growing demand for personal health management tools and digital wellness platforms.
Stakeholder coinvolti: [Elenco puntato degli stakeholder principali]

<br><br>

# 2. Descrizione del Progetto

## 2.1 Obiettivi del Progetto
    1. simple and clear user interface
    2. create a real shopping reminder
    3. create a real virtual pantry
    4. advertise to increase customers.

<br>

## 2.2 Caratteristiche Principali
Funzionalità chiave: 
•⁠ plan your weekly diet
•⁠ manage clients and their diets
•⁠ communication between client and nutritionist
•⁠ list of missing ingredients for the next meal
•⁠ check your progress
•⁠ manage your appointments

<br>

## 2.3 Requisiti Fondamentali
Tecnici: 
•  Use of Xample’s cloud infrastructure to ensure scalability, security, and high availability.
•  Mobile app (iOS and Android) developed using Xample-supported frameworks such as Flutter or React Native.
•  Responsive web application integrated within Xample’s ecosystem, optimized for multiple devices.
•  Relational database (e.g., PostgreSQL) managed through Xample’s cloud services.
•  Authentication system integrated with Xample’s security protocols (e.g., OAuth 2.0, two-factor authentication).
•  RESTful APIs built according to Xample’s architectural standards for seamless integration.

Operativi: 
•  Collaboration with Xample’s development team (frontend, backend, mobile).
•  Involvement of Xample’s UX/UI designers to ensure a clean and user-friendly interface.
•  Support from external nutrition experts or through Xample-recommended partners.
•  Project management aligned with Xample's agile practices (e.g., Scrum).
•  Technical support and customer service handled through Xample’s support channels.
•  Testing infrastructure embedded in Xample’s CI/CD pipeline.

Normativi: 
•⁠  ⁠GDPR compliance ensured through Xample’s built-in data protection policies.
•⁠  ⁠Adherence to relevant health and nutrition regulations, where applicable.
•⁠  ⁠Legal documentation (terms of service, privacy policy) prepared in collaboration with Xample’s legal advisors.
•⁠  ⁠Accessibility ensured via WCAG 2.1 guidelines, supported by Xample’s accessibility tools.


<br><br>

# 3. Analisi di Mercato

## 3.1 Analisi della Domanda
Target Customers:
• Individuals aged 20 to 45 living in urban areas with busy professional lives.
• Families seeking to better manage grocery shopping and weekly meals.
• Health-conscious users looking for digital tools to maintain a balanced diet.
• Consumers interested in reducing food waste and living more sustainably.
Market Size:
• According to Statista and Euromonitor, the global health and wellness app market is projected to exceed $150 billion by 2030, with an annual growth rate of 15–20%.
• In Italy, over 70% of consumers report wanting to eat healthier and more sustainably, yet 60% find it difficult to plan meals effectively.
• Approximately 35% of Italian users currently use at least one app related to diet tracking, grocery shopping, or personal health.
Market Trends:
• Growing interest in digital meal planning and nutrition assistance.
• Increasing consumer focus on healthy eating and waste reduction.
• Rise of the smart kitchen model: integration between apps and home appliances.
• Widespread use of AI and personalized notifications in health-related apps.

<br>

## 3.2 Analisi della Concorrenza
Competitor     |                  Strengths                          |         Weaknesses                                 |     Estimated Market Share
------------------------------------------------------------------------------------------------------------------------------------------------------------
Yazio          |      Intuitive interface, personalized meal plans   | No pantry management, limited free version         |     High (Italian market leader)
------------------------------------------------------------------------------------------------------------------------------------------------------------
Lifesum        |        Customization, integration with wearables    | Doesn’t track available ingredients at home        |               Medium
------------------------------------------------------------------------------------------------------------------------------------------------------------
MyFitnessPal   |         Extensive food database, active community   | Focuses more on calorie counting than planning     |               High
------------------------------------------------------------------------------------------------------------------------------------------------------------
Too Good To Go |    Strong sustainability focus, combats food waste  |  No weekly meal planning or pantry features        |      N/A (not direct competition)

<br>

## 3.3 Analisi SWOT TODO better markdown table

Tabella SWOT:


Strengths                                                            Weaknesses
Integrated pantry and weekly meal planner           Requires manual input of initial pantry inventory
Smart notifications to avoid food waste                May be complex for non-tech-savvy users
Real ingredient-based suggestions and planning       Dependent on user consistency for accurate tracking                 


Opportunities                                         |                      Threats
----------------------------------------------------------------------------------------------------------------                                     
Rising consumer focus on health and sustainability    |      Intense competition in the food/health app market
----------------------------------------------------------------------------------------------------------------
Potential for e-commerce integration                  |   Low user retention if not engaging (e.g. gamification)
----------------------------------------------------------------------------------------------------------------
Smart home/IoT device integration                     |    Regulatory risks around data privacy and food info

<br>

## 3.4 Valore per il Cliente
A smart platform that connects pantry management, meal planning, and real-time alerts to reduce food waste and support healthier eating—based on the food you actually have at home.
Customer Benefits:
• Healthier, stress-free weekly meal planning
• Significant reduction in food waste
• Time and cost savings in grocery shopping
• Smart alerts based on pantry inventory
• Adaptable to various dietary needs (e.g., vegetarian, keto, etc.)
• Better household budget management
<br><br>

# 4. Analisi Tecnica + 4.1 Soluzione tecnica proposta

The technical solution for "YourWeek" will be developed as a Web Application accessible via desktop and mobile browsers. It will leverage a client-server architecture with an API-first approach to ensure scalability and future integration with potential native mobile applications.
<br>

## 4.2 Requisiti Tecnici
Infrastruttura: [Descrizione dell'infrastruttura necessaria]
## Software:
•XAMPP (Apache, PHP, MySQL, phpMyAdmin)
•HTML, CSS, JavaScript for front-end development
•PHP for server-side logic
•SQL/MySQL for database management
•Optional use of frameworks (e.g., Laravel, React, or Vue.js) in future phases

## Hardware:
•Local server (development PC with XAMPP)
•Remote web server for deployment (shared hosting or VPS)
•Database server integrated with MySQL
•Client devices such as PCs, smartphones, and tablets for user access

## Sicurezza: 
•HTTPS (SSL/TLS) for encrypted communication
•Message encryption for secure chat or messaging feature
•SQL Injection and XSS protection using prepared statements and data sanitization
•Secure authentication with hashed passwords (e.g., bcrypt)
•Regular database backups and server monitoring

## Scalabilità: 
•Easy migration from local XAMPP environment to dedicated or cloud servers (AWS, Google Cloud, etc.)
•Modular code structure for adding new features
•REST API integration to support the mobile application and other services

## Manutenzione: 
•Regular updates for PHP, MySQL, and security patches
•Server and database monitoring to ensure performance and uptime
•Automated backups at regular intervals
•Technical support for bug fixes and future improvements

## 4.3 Fattibilità Tecnica
Tecnologie disponibili: [Elenco delle tecnologie attualmente disponibili]
Competenze del team: artificial intelligence, technical skills learned at school and self-taught
Fornitori e partner: 
Rischi tecnici: 
Prototipi e test: [Descrizione di eventuali prototipi o test preliminari]

<br><br>

# 5. Analisi Economica Finanziaria

## 5.1 Stima dei Costi
| Categoria        | Investimento Iniziale (€) | Costi annuali (€) |
|------------------|---------------------------|-------------------|
| Personnell       |          [60,00]      | [Valore]          |
| Hardware     |              [5,00]       | [Valore]          |
| Software     |              [20,00]      | [Valore]          |
| Training     | [Valore]                  | [Valore]          |
| Marketing    | [Valore]                  | [Valore]          |
| Altro        | [Valore]                  | [Valore]          |
| Totale       | [Valore]                  | [Valore]          |

<br>

## 5.2 Stima dei Ricavi
| Fonte di Ricavo  | Descrizione               | Ricavi annuali (€) |
|------------------|---------------------------|--------------------|
| Vendite      | [Descrizione]             | [Valore]           |
| Abbonamenti  | [Descrizione]             | [Valore]           |
| Pubblicità   | [Descrizione]             | [Valore]           |
| Altro        | [Descrizione]             | [Valore]           |
| Totale       |                           | [Valore]           |   

[Proiezione di fatturato - meglio se conservative]

<br>

## 5.3 Indicatori di Redditività
ROI (Return on Investment): [%] - [Calcolo: (Guadagno - Investimento)/Investimento]
<br>
Payback Period: [Numero di mesi/anni per rientrare dall'investimento]
<br>
VAL/NPV (Valore Attuale Netto): [Valore]
<br>
TIR/IRR (Tasso Interno di Rendimento): [%]
<br>

[Compilare file del progetto come file ROI_Payback_VAL_TIR.md]

<br>

## 5.4 Break-even Analysis
Punto di Pareggio: [Descrizione del punto di pareggio]
<br>
Volume di Vendite Necessario: [Quantità di vendite necessarie per coprire i costi]

<br><br>

# 6. Analisi Organizzativa

## 6.1 Struttura Interna
Ruoli e Responsabilità: [Elenco puntato dei principali ruoli e responsabilità]
<br>
Nuove figure professionali: [Descrizione delle nuove figure professionali necessarie]
<br>
Formazione: [Piani di formazione per il personale]
<br>


## 6.2 Struttura di Project Management
Responsabile di Progetto: [Nome e ruolo]
<br>
Team di Progetto: [Elenco dei membri del team]
Metodologia di Gestione: [Descrizione della metodologia di gestione del progetto (es. Agile, Waterfall)]
Strumenti di Gestione: [Elenco degli strumenti di gestione del progetto (es. Jira, Trello)]

<br><br>

# 7. Analisi dei Rischi

## 7.1 Identificazione dei Rischi
| Rischio                | Descrizione               | Probabilità (1-5) |
|-----------------------|---------------------------|-------------------|
| [Esempio]             | [Esempio]                 | [Valore]
| [Esempio]             | [Esempio]                 | [Valore]           |
| [Esempio]             | [Esempio]                 | [Valore]

<br><br>

# 8. Piano di Implementazione

## 8.1 Fasi del Progetto

1.⁠ ⁠Fase 1 - Avvio: [Attività e durata]
2.⁠ ⁠Fase 2 - Sviluppo: [Attività e durata]
3.⁠ ⁠Fase 3 - Testing: [Attività e durata]
4.⁠ ⁠Fase 4 - Implementazione: [Attività e durata]

<br>

## 8.2 Tempistiche
Durata Totale del Progetto: [Numero di mesi/anni]
Milestone Principali: [Elenco puntato delle principali milestone con date previste]

[Gantt - Diagramma di Gantt in allegati]

<br><br>

# 9. Conclusioni e Raccomandazioni

## 9.1 Sintesi della Valutazione
Vantaggi principali: [Elenco puntato]
Svantaggi/Sfide: [Elenco puntato]

<br>

## 9.2 Raccomandazione Finale
[FATTIBILE] - Se i benefici superano rischi e costi
[NON FATTIBILE] - Se i rischi/costi sono troppo alti
[FATTIBILE CON CONDIZIONI] - Se dipende da specifici fattori

<br><br>

# X. Allegati (cartella docs/fattibilita/allegati)
Alcuni allegati che possono essere utili per lo studio di fattibilità:
•⁠  ⁠Preventivi dei fornitori per hardware/software
•⁠  ⁠Costi stimati (sviluppo, marketing, operativi)
•⁠  ⁠Analisi dei concorrenti
•⁠  ⁠Analisi di mercato e feedback degli utenti potenziali
•⁠  ⁠SWOT analysis (Strengths, Weaknesses, Opportunities, Threats)
•⁠  ⁠Gantt (pianificazione temporale risorse e attività)
•⁠  ⁠Analisi dei rischi


mermaid
erDiagram
    CUSTOMER ||--o{ ORDER : places
    ORDER ||--|{ LINE-ITEM : contains
    CUSTOMER {
        string name
        string email
        int customerId
    }
    ORDER {
        int orderId
        date orderDate
    }