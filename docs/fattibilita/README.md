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
Tecnologie disponibili: The stack is purely client-side, with XAMPP (PHP) used only for hosting and potentially very lightweight backend logic (if used). Server/Hosting (XAMPP): Apache for serving web pages. PHP: Can be used for simple server-side logic or to dynamically generate code, but not for persistent data management (without a database). Frontend (User Interface & Data): HTML & CSS: Application structure and style. JavaScript: Manages all interactivity, algorithm logic, and, most importantly, data persistence. Data Management (Without SQL) The key is storing all data (handouts, plans, recipes). Since the database is excluded, the only solution for "remembering" data between sessions is local browser storage.

Competenze del team: Skills must focus on data manipulation in JavaScript format and algorithmic logic implemented entirely on the client side.
Web Storage Management: Specific skills in saving, retrieving, and updating data via localStorage. Programming Logic: Required for implementing the JavaScript algorithm that compares two large data sets (Meal Plan and Pantry Inventory). Artificial Intelligence / Algorithm: The comparison algorithm will be written in JavaScript. It will use rules-based logic and loops (for, if/else) to iterate over the objects stored in localStorage and determine which ingredients are missing.

Fornitori e partner: 
Rischi tecnici: The main risk is persistence and scalability, which are inherent in choosing not to use a database.
In this project we find technical risk with different impact [high/medium/low], this risks are:

- User Data Loss [High]: Clearly warn the user that data is saved only locally in their browser, and deleting site data or cache will erase it.
- localStorage Speed/Limits [Medium]: Test the application with a large volume of recipes/pantry items to ensure that JavaScript processing does not excessively slow down the interface.
- Algorithm Complexity (Client-Side) [Medium]: Design the JSON data structure in an optimized way to facilitate rapid comparison of elements by the JavaScript algorithm.
- Security (Credentials/Sensitive Data) [Low]: As there are no sensitive credentials or external databases, the risk is minimal.

Prototipi e test: Step 1: Working Persistence: Test whether an item is actually saved in the pantry and whether it is correctly reread after reloading the page (checking localStorage). Step 2: JavaScript Logic: Test the comparison algorithm: Add specific ingredients to the meal plan and verify whether they appear in the shopping list based on their presence in localStorage. Step 3: Robustness: Simulate deleting browser data to demonstrate and understand the limitations of local persistence.

<br><br>

# 5. Analisi Economica Finanziaria

## 5.1 Stima dei Costi
| Categoria        | Investimento Iniziale (€) | Costi annuali (€) |
|------------------|---------------------------|-------------------|
| Personnell       |          [800.00]      | [1500.00]          | 
| Hardware     |              [0,00]       | [50.00]          |
| Software     |              [50,00]      | [0.00]          |
| Training     | [0.00]                  | [0.00]          |
| Marketing    | [400.00]                  | [600.00]          |
| Altro        | [80.00]                  | [120.00]          |
| Totale       | [1330.00]                  | [2270.00]          |

<br>

## 5.2 Stima dei Ricavi
| Fonte di Ricavo  | Descrizione               | Ricavi annuali (€) |
|------------------|---------------------------|--------------------|
| Vendite      | [One-time purchases for         [800.00]
                 recipe template packs or
                 customized UI themes to
                 enhance the user experience] |                    |
| Abbonamenti  | ["Panty Pro" membership         [4500.00]
                 offering features like 
                 cloud-based data backup 
                 (overcoming the localStorage 
                 limit) and multi-device 
                 synchronization]             |               |
| Pubblicità   | [Dysplaying non-intrusive       [1200.00]
                 banner ads fron food brands,
                 grocery stores, or kitchen
                 appliance companies in the
                 free version]                |                 |
| Altro        | [Generating commissions by      [500.00]
                 interacting direct links to
                 online grocery stores
                 within the automatically
                 generated shopping list]    |                  |
| Totale       |                           | [7000.00]           |   

[Proiezione di fatturato - meglio se conservative]
This projection assumes a cautious and gradual market penetration after the public launch.
User Base Assumption:
Total Active Users (Year 1): 750
Subscription Conversion Rate (Subscriptions): 10% (75 paying users)
Subscription Price: $5/month or $60/year.
Subscription Revenue Calculation:
75 users X 60€/year = 4,500€
Total Estimated Revenue (Year 1):
Total Revenue \approx 7,000.00€
Break-Even Point Analysis:
The project's low annual operating costs (primarily hosting, maintenance, and marketing) make it relatively easy to cover expenses. The estimated profit in the first year of operation would be:
Total Revenue - Annual Costs = 7,000.00 € - 2,370.00€ = 4,630.00€
The project is economically viable under these conservative projections.

<br>

## 5.3 Indicatori di Redditività
We will use the following figures from the previous estimates for the calculations:
​Total Initial Investment: I = €1,480.00
​Estimated Annual Net Earnings (Cash Flow): G = €4,630.00
​ROI (Return on Investment)
​The ROI measures the efficiency of the initial investment. It indicates the gain obtained relative to the capital invested, based on the annual net earnings (Cash Flow).
​Formula: ROI = Annual Net Earnings /Initial Investment X 100
​Calculation: ROI = 4,630.00€/1,480.00€ X 100 ≈ 312.84
​Payback Period
​The Payback Period is the time required for the cash flows generated by the project to cover the Initial Investment.
​Formula: Payback Period = Initial Investment/Annual Net Earnings X 12 months
​Calculation: Payback Period = 1,480.00€/4,630.00€ X 12 months ≈ 3.84 
​NPV (Net Present Value)
​The NPV calculates the present value of all future cash flows, discounted at a rate (r) that reflects the cost of money over time.

Assumption: Discount Rate (r) = 7%; Horizon (T) = 3 years; G_t = €4,630.00.

Calculation:
NPV ≈ 4,630/(1.07)^1 + 4,630/(1.07)^2 + 4,630/(1.07)^3 - 1,480 ≈ 10,670.58€

IRR (Internal Rate of Return)
The IRR is the discount rate that makes the NPV equal to zero. It represents the project's annual rate of return.
Value: Extremely High (\mathbf{> 200\%}) (Since the Payback Period is under one year, the IRR is far above any standard cost of capital.)

<br>

[Compilare file del progetto come file ROI_Payback_VAL_TIR.md]

<br>

## 5.4 Break-even Analysis
Punto di Pareggio: Break-even Point (BEP)
​The Break-even Point is the level of activity where Total Revenue equals Total Costs (Initial Investment + Annual Fixed Costs + Variable Costs), resulting in zero profit.
Break-even Point: The project reaches the break-even point when cumulative revenues manage to cover the initial investment of €1,480.00 and the annual operating costs of €2,370.00.
Necessary Sales Volume
We calculate the number of annual paying users required to cover only the annual operating costs (€2,370.00), assuming the main revenue is derived from annual subscriptions.
Annual Fixed Costs: C = €2,370.00
Average Annual Subscription Price: 
Abbonamento = 60.00€
Necessary paying user = 2.370.00€/60.00€ ≈ 39.5 user
Necessary Sales Volume: Approximately 40 subscribed users (or the equivalent in advertising/sales revenue) are needed to cover all annual fixed operating costs.
Would you like to review any other section of the feasibility study, or proceed with new content?

<br><br>

# 6. Analisi Organizzativa

## 6.1 Struttura Interna
Ruoli e Responsabilità: Cannata Nicola (gestore progetto): pensa a gestire il progetto, dare compiti, creare l'htmo e il css
Gentilcore Lorenzo (gestore documentazione): si occupa della fattibilità
Verzeroli Mirko (dipendente): segui le direttive del gestore progetto
<br>
Nuove figure professionali: 
<br>
Formazione: [Piani di formazione per il personale]
<br>


## 6.2 Struttura di Project Management
Responsabile di Progetto: Cannata Nicola
<br>
Team di Progetto:
Cannata Nicola 
Gentilcore Lorenzo
Verzeroli Mirko 
Metodologia di Gestione: Agile: based on collaboration, flexibility and continuous delivery of value
Strumenti di Gestione: Visual studio, xampp, github 

<br><br>

# 7. Analisi dei Rischi

## 7.1 Identificazione dei Rischi
This table identifies the key risks for the project, categorized into technical, managerial, and operational areas, and estimates their likelihood of occurrence on a scale of 1 (Very Low) to 5 (Very High).
| Rischio                | Descrizione               | Probabilità (1-5) |
|-----------------------|---------------------------|-------------------|
| Data Persistence 
  Failure               |The user clears their browser cache or switches devices, resulting in the critical loss of all pantry inventory and meal planning data because the data is stored exclusively on the client-side using localStorage and not a central database             | 5
| JS Algorithm 
  Complexity Errors     | Logical flaws or bugs in the JavaScript algorithm responsible for comparing the planned meals against the pantry inventory, leading to the generation of incomplete or incorrect shopping lists. This directly affects the core utility and reliability of the application.                | 4           |
| Code Scalability 
  issues                | As the project grows, the core JavaScript codebase may become too large and complex to manage or update efficiently. This will make adding new features (like user accounts or new views) slow and technically risky.                | 3
| Development Delays    | The team underestimates the time needed for debugging the complex interaction between the different JS sections, particularly the reading, processing, and writing back of large data structures to localStorage.                 | 3
| Poor user feedback.   | The interface, especially the crucial process of rapidly entering pantry items, proves difficult or inefficient for the end-user. This lack of usability can lead to low adoption rates or user abandonment.                 | 2           |
| Environment 
  availability          | Configuration issues or XAMPP incompatibility between different team members' development environments, creating friction and hindering efficient collaboration on the code.                | 2
| Future PHP  
  Vulnerabilities       | If the project were to be moved online or expanded to include any PHP form handling, insecure server-side code (even if minimal) could present security flaws (e.g., Cross-Site Scripting or basic injection issues).                 | 1
  

<br><br>

# 8. Piano di Implementazione

## 8.1 Fasi del Progetto

1.⁠ ⁠Fase 1 - Avvio: (2weeks)
- Setup and Data Design: Configure the local XAMPP environment and set up the code repository.
- The most crucial activity is defining the precise JavaScript Data Architecture (localStorage/JSON), which dictates how recipes, pantry items, and meal plans will be structured and saved without a database.
- Create the initial design mock-ups (wireframes) for the user interface.
2.⁠ ⁠Fase 2 - Sviluppo:(6 Weeks):
This phase involves three iterative Sprints.
Sprint 1 (2 weeks): Focus on the Pantry Section implementation, including data input, basic persistence in localStorage, and display.
Sprint 2 (2 weeks): Develop the Meal Planning Section, allowing users to create and visualize their weekly plan.
Sprint 3 (2 weeks): Implement and thoroughly refine the Automatic Algorithm. This involves the complex JavaScript logic that compares the items in the pantry data against the required items in the meal plan to correctly generate the final shopping list.
3.⁠ ⁠Fase 3 - Testing: (1 Week):
​Functional and User Testing: Perform focused unit tests on critical JavaScript functions, especially those interacting with localStorage.
​Conduct User Testing (Beta) with a small group to evaluate the application's usability (UX) and overall stability.
​Intensive bug fixing based on the feedback received.
4.⁠ ⁠Fase 4 - Implementazione: (1 Week):
​Finalization and Release: Perform final code cleanup and optimization (CSS/JS compression).
​Create comprehensive user and technical documentation.
​Prepare and deliver the MVP Prototype within the configured XAMPP environment.

<br>

## 8.2 Tempistiche
Durata Totale del Progetto: 9 month
Milestone Principali: ​M1: Design Approved: Achieved by the end of Week 2, including approval of wireframes and the JSON data schema.
M2: Pantry Section Functional: Achieved by the end of Week 4. The core data input and persistence for the Pantry section are fully operational.
M3: Core Logic Complete: Achieved by the end of Week 8. The Comparison Algorithm is fully implemented and correctly generates the shopping list with high accuracy.
M4: MVP Prototype Ready: Achieved by the end of Week 9. All major bugs have been resolved, and the system has successfully passed internal usability tests.
M5: Final Delivery: Achieved by the end of Week 10. Formal delivery of the complete project package (Code, XAMPP setup, and Documentation).

[Gantt - Diagramma di Gantt in allegati]

<br><br>

# 9. Conclusioni e Raccomandazioni

## 9.1 Sintesi della Valutazione
This summary assesses the key outcomes of the project, focusing on balancing the benefits of implementing the solution against the challenges imposed by the chosen technological stack (XAMPP, HTML, CSS, JavaScript (No SQL)).
​Key Advantages:
​Low Initial Cost: The reliance on free technologies and the absence of a database significantly reduce the initial investment to a minimum (€1,480.00).
​High Profitability: The project exhibits a very short Payback Period (under 4 months) and an exceptionally high ROI (312.84\%), indicating strong economic viability.
​Minimal Technical Requirements: The stack is widely known and easy to access, making the project ideal for a team with school-based and self-taught skills.
​Addresses a Real Problem: The system effectively solves the common need to reduce food waste and simplify weekly grocery shopping.
​Rapid Development (Agile): The Scrum methodology allows for the delivery of the MVP prototype within a short timeframe (10 weeks).
​Disadvantages/Challenges:
​Critical Data Loss Risk: Dependence on localStorage means user data can be easily deleted, severely limiting long-term reliability and trustworthiness.
​Limited Scalability: The system is inherently non-scalable. It cannot support a large volume of users or evolve into a multi-device application without a mandatory migration to a central database and cloud hosting.
​JavaScript Complexity: The core comparison algorithm must be entirely handled by client-side JavaScript, making debugging and optimization more complex than a robust server-side implementation.
​Zero Portability: Without a centralized server, the project is confined to a single device/browser.
<br>

## 9.2 Raccomandazione Finale
Justification for the Recommendation
​The project is technically achievable with the provided stack and is highly economically advantageous given the minimal initial costs. However, it carries a critical technical risk related to data loss and lack of scalability, which prevents its full feasibility as a long-term commercial product.
​Conditions for Success:
​Short-Term Goal: The project is FEASIBLE and highly recommended as a Working Prototype (MVP) and Proof of Concept for academic submission or demonstration.
​Long-Term Goal: For future commercialization, feasibility is contingent upon the immediate migration of the project to a technology stack with a centralized database (e.g., PHP/MySQL, Node.js, Python/PostgreSQL) to resolve the data persistence issue and ensure scalability.

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
