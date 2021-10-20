# product-ordering-chatbot
Product ordering chatbot

This system has been written in following languages:
* PHP – scripts that handle received messages from Facebook page (almost entire solution)
* Java (Selenium Framework) – program that performs ordering process and returns total cost to the caller-script

As this system totally depends on Facebook (to be exact, on its Messenger <i>module</i>), features of our solution can be utilized via all platforms on which Facebook is supported, i.e. via any web-browser and via Messenger applications of any operating system (Android, iOS, Windows Phone, …).

Purpose of this chatbot is to be a mediator between <b>users</b> on social networks who are <i>potential customers</i> and <b>vendors</b> with <i>goods</i> they offer. Vendors’ websites are often too complex and have too strict search-engines for basic Internet-users that are barely and rarely utilizing the potential of the Internet and its services. Also, comparing products by various vendors is often time-consuming, even for proficient Internet users. Therefore, goal of our agent is to extract relevant information from user’s unstructured query and to return him/her personalized results, that are matching specified criteria, in visualized and interactive form. Moreover, most of users like to accomplish desired task with few clicks/taps and preferably on the single place (application/website), so our system should provide user access to more detailed information on request and make routine (often tiresome and error-prone) tasks easier and automatized-as-possible.

To begin with, we are providing UML sequence diagram that represents communication flows between all actors and their components. Actors/nodes in the system are following:
* ![#baa2c5](https://via.placeholder.com/15/baa2c5/000000?text=+) potential customer (user on social network with specific needs)
* ![#007fff](https://via.placeholder.com/15/007fff/000000?text=+) [our facebook page](https://www.facebook.com/Helix-Nebula-1774756716186905/) (user contacts it in order to get details about vendors’ offers)
* ![#d78e8b](https://via.placeholder.com/15/d78e8b/000000?text=+) our components/scripts (automatically work on message that user sent to our facebook page, user closest retail shop or to calculate delivery date, check product-availability or criteria-satisfaction)
* ![#a9cc9a](https://via.placeholder.com/15/a9cc9a/000000?text=+) Google services (support our components by interpreting user’s message and extracting relevant information, preparing text for NLP by translating it in one of supported languages, and providing set of geographic data and services)
* ![#ebbf63](https://via.placeholder.com/15/ebbf63/000000?text=+) vendors’ web-services and web-pages (sources from which product information is fetched and destinations where user’s orders are sent)

![image](/images/sequence_diagram.png?raw=true "UML sequence diagram of main communication")

At the bare beginning (it is not shown in upper visualization), user has to register themselves and to provide some basic information what is required in order to respond him/her with personalized results and to perform automatized check-out when purchasing some product. This registration process contains of 3 parts:
1. asking user for address of destinations where goods will be delivered (this is even necessary if the user just wants to order something and to pick it up by themselves because user’s residential address often has to be on a receipt) – location is verified by Google’s geocoding service, so if the user submits non-existent or unprecise address, they have to repeat this step, but this time with providing additional information like zip-code and/or country name whose lack causes ambiguity
2. asking user for their e-mail address they can be contacted by vendor or deliverer
3. asking user for their phone number they can be contacted by vendor or deliverer

![image](/images/registration.png?raw=true "Example of registration process")

In the picture above, the entire registration process is shown, including allowed flexibility of user’s input (phone number and location address can be in various formats) and input validation (specified address is valid as long as there can be found exactly one geographic location for it). After that process is completed, user can continue to communicate with our Facebook page in order to get information about desired products or even to purchase them.

Following ordering/purchasing requests can be forwarded in relatively flexible form because user’s message is forwarded to Google’s Cloud Natural Language Processing API, so keywords could be extracted. But, as it doesn’t support Croatian language, message is previously translated by Google’s Translate API and the English message variant is sent on interpretation. As result of natural language analysis is in English language and current vendor’s website is in Croatian language, the extracted keywords have to be translated back into Croatian language.

What comes next is performing queries on vendor’s search-engine based on keywords from previous phase – currently, we are using web-shop by company [Links d.o.o.](https://www.links.hr/hr/) which is one of the leading croatian vendors of computers, their components and other electronics. Since their web-shop is relatively well-organized (each product is inside categories that represent product’s type (e.g. laptops, CPUs, GPUs, RAM, …) and its manufacturer (e.g. Asus, Intel, Logitech, …)), we use [search section](https://www.links.hr/hr/search) of their website which form we “fill” with user’s request/criteria, handle the response HTML document of submitted form and then we are displaying to user up to 10 search result items (because that’s the maximum capacity of carousel of [Facebook’s generic templates](https://developers.facebook.com/docs/messenger-platform/send-messages/template/generic)) or suitable message about not finding any results. In the picture below is the preview of one item of result list with belonging button for ordering it.

![image](/images/generic_template.png?raw=true "Example of the corresponding list items in generic template")

When clicking on item’s picture, a dialog shows up with product’s web-page on vendor’s web-shop and there is user able to find the details about that product. Product is shown in the following picture:

![image](/images/product_details_dialog.png?raw=true "Detailed information about specific product.")

After that, it is user’s turn – if the user would like to buy a product, they simply press the button located at the bottom of corresponding item, otherwise they can end chat session (i.e. do not send anything) or perform some another query.

When user clicks on the button for buying desired product, our system checks on vendor’s web-site if specified product is even available and if so, finds closest vendor’s retail store (using Google’s Distance Matrix API) where the product is available. In that case, the user is asked if they want to pick-up product by themselves in the closest retail store, if they want it delivered to their location or if they want to cancel the ordering process. In the case that product is not available, user is informed that specified product is not currently available. Preview of that response is in the following format:

![image](/images/quick_replies.png?raw=true "Available quick-replying options for selecting payment method")

Now again it is on user to select one of the available options. If the user proceeds with purchase, then the Java program is executed that simulates ordering process on vendor’s web-shop in a headless-browser (currently is PhantomJS used). Ordering process consist of putting product in the shopping-cart, proceeding to check-out, filling form with customer’s personal information, selecting shipment and payment method, and finally accepting terms and conditions. The only supported payment method for now is COD (cash on delivery). That automatized process is relatively expensive (from the aspect of time execution) and can take about up to half a minute. At the end, it results with total price which is sent back to the user in form of the [Facebook receipt template](https://developers.facebook.com/docs/messenger-platform/send-messages/template/receipt). User can check details of the purchase by clicking on that element. Following image displays details of processed order.

![image](/images/order_details.png?raw=true "Order details of purchased product")

Below I will show 2 scenarios (excluding registration process as it was already completely shown at the top of this page). In the first example, user is interested in specific product (Logitech’s mouse G203) that is ordered and will be delivered to the location which user has registered.

![image](/images/case1.png?raw=true "First use-scenario")

In the second example user wants to buy graphics card which price is between 1000 and 2000 HRK. After the result items, which satisfy specified criteria, are listed, user selects that they want to buy one of the products (in this case used Gainward GeForce GTX 960), but as it is currently unavailable in all vendor’s stores and even in their main warehouse (not surprising because of the trending crypto-mania). That’s the reason why user can’t order this product and proceed with payment.

![image](/images/case2.png?raw=true "Second use-scenario")
