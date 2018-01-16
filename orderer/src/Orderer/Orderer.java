/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package Orderer;

import java.nio.file.Paths;
import java.util.List;
import java.util.logging.Level;
import java.util.logging.Logger;
import org.openqa.selenium.By;
import org.openqa.selenium.JavascriptExecutor;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import org.openqa.selenium.phantomjs.PhantomJSDriver;
import org.openqa.selenium.support.ui.Select;

/**
 *
 * @author zeko868
 */
public class Orderer {

    private static WebDriver driver = null;

    public static void main(String[] args) {
        if (args.length >= 11 && args.length%2 == 1) {
            String firstName = args[0];
            String lastName = args[1];
            String email = args[2];
            String address = args[3];
            String zipCode = args[4];
            String cityName = args[5];
            String countryCode = args[6];
            String phoneNum = args[7];
            String pickupStore = args[8];
            
            //System.setProperty("webdriver.chrome.driver", Paths.get(System.getProperty("user.dir"), "chromedriver.exe").toString());
            //System.setProperty("webdriver.chrome.driver", "/usr/local/bin/chromedriver");
            //driver = new ChromeDriver();
            System.setProperty("phantomjs.binary.path", "vendor/phantomjs/bin/phantomjs");
            //System.setProperty("phantomjs.binary.path", "D:\\Users\\zeko868\\Documents\\GitHub\\foi-konzultacije\\orderer\\phantomjs.exe");
            driver = new PhantomJSDriver();
            //driver = new HtmlUnitDriver(true);
            
            for (int i=9; i<args.length; i+=2) {
                String productUrl = args[i];
                int quantity = Integer.parseInt(args[i+1]);
                driver.get(productUrl);
                WebElement quantityBox = driver.findElement(By.className("qty-input"));
                quantityBox.clear();
                quantityBox.sendKeys(String.valueOf(quantity));
                try {
                    Thread.sleep(500);  // delay required before adding to cart
                } catch (InterruptedException ex) {
                    Logger.getLogger(Orderer.class.getName()).log(Level.SEVERE, null, ex);
                }
                driver.findElement(By.className("add-to-cart-button")).click();
            }

            try {
                Thread.sleep(500);  // delay required before performing check-out
            } catch (InterruptedException ex) {
                Logger.getLogger(Orderer.class.getName()).log(Level.SEVERE, null, ex);
            }
            //driver.get("https://www.links.hr/hr/onepagecheckout");
            //driver.findElementByClassName("checkout-button").click();

            ((JavascriptExecutor)driver).executeScript("setLocation('/hr/login/checkoutasguest?returnUrl=%2Fhr%2Fcart');");
            try {
                Thread.sleep(3000);
            } catch (InterruptedException ex) {
                Logger.getLogger(Orderer.class.getName()).log(Level.SEVERE, null, ex);
            }

            /*            
            driver.findElement(By.className("cartHolder")).click();
            driver.findElement(By.id("checkout")).click();
*/
            driver.findElement(By.className("checkout-as-guest-button")).click();

            try {
                Thread.sleep(3000);
            } catch (InterruptedException ex) {
                Logger.getLogger(Orderer.class.getName()).log(Level.SEVERE, null, ex);
            }
/*            // works fine in Chrome
            driver.findElement(By.id("BillingNewAddress_FirstName")).sendKeys(firstName);
            driver.findElement(By.id("BillingNewAddress_LastName")).sendKeys(lastName);
            driver.findElement(By.id("BillingNewAddress_Email")).sendKeys(email);
            driver.findElement(By.id("BillingNewAddress_Address1")).sendKeys(address);
            if (zipCodeOrCity.matches("^\\d+$")) {  // usually it could also contains characters, but in Croatia/Slovenia/Bosnia/Serbia there are only digits
                driver.findElement(By.cssSelector("label[for=\"BillingNewAddress_ZipPostalCode\"] ~ input")).sendKeys(zipCodeOrCity);
            }
            else {
                driver.findElement(By.cssSelector("label[for=\"BillingNewAddress_City\"] ~ input")).sendKeys(zipCodeOrCity.toUpperCase());
            }
            driver.findElement(By.id("BillingNewAddress_PhoneNumber")).sendKeys(phoneNum);
*/
            JavascriptExecutor jsExecutor = ((JavascriptExecutor) driver);
            jsExecutor.executeScript("document.getElementById('BillingNewAddress_FirstName').value = arguments[0];", firstName);
            jsExecutor.executeScript("document.getElementById('BillingNewAddress_LastName').value = arguments[0];", lastName);
            jsExecutor.executeScript("document.getElementById('BillingNewAddress_Email').value = arguments[0];", email);
            jsExecutor.executeScript("document.getElementById('BillingNewAddress_Address1').value = arguments[0];", address);
            jsExecutor.executeScript("document.getElementById('BillingNewAddress_ZipPostalCode').value = arguments[0];", zipCode);
            jsExecutor.executeScript("document.getElementById('BillingNewAddress_City').value = arguments[0];", cityName);
            jsExecutor.executeScript("document.getElementById('BillingNewAddress_CountryId').value = arguments[0];", countryCode);
            jsExecutor.executeScript("document.getElementById('BillingNewAddress_PhoneNumber').value = arguments[0];", phoneNum);
            

            driver.findElement(By.cssSelector("#billing-buttons-container > .new-address-next-step-button")).click();

            while (true) {
                try {
                    Thread.sleep(100);
                } catch (InterruptedException ex) {
                    Logger.getLogger(Orderer.class.getName()).log(Level.SEVERE, null, ex);
                }
                if ("none".equals(driver.findElement(By.id("billing-please-wait")).getCssValue("display"))) {
                    break;
                }
            }
            
            //works fine for Chrome
            //((JavascriptExecutor) driver).executeScript("Shipping.save();");
            //also works fine for Chrome
            //((JavascriptExecutor) driver).executeScript("document.getElementById(\"shipping-buttons-container\").getElementsByClassName(\"new-address-next-step-button\")[0].click();");
            driver.findElement(By.cssSelector("#shipping-buttons-container > .new-address-next-step-button")).click();
            
            
            //((JavascriptExecutor) driver).executeScript("arguments[0].click();", driver.findElement(By.cssSelector("#shipping-buttons-container .new-address-next-step-button")));
            //((PhantomJSDriver)driver).executePhantomJS("document.getElementById(\"shipping-buttons-container\").getElementsByClassName(\"new-address-next-step-button\")[0].click();");
            //((PhantomJSDriver)driver).executePhantomJS("Shipping.save();");
            
            while (true) {
                try {
                    Thread.sleep(100);
                } catch (InterruptedException ex) {
                    Logger.getLogger(Orderer.class.getName()).log(Level.SEVERE, null, ex);
                }
                if ("none".equals(driver.findElement(By.id("shipping-please-wait")).getCssValue("display"))) {
                    break;
                }
            }
            
            try {
                Thread.sleep(2000);
            } catch (InterruptedException ex) {
                Logger.getLogger(Orderer.class.getName()).log(Level.SEVERE, null, ex);
            }

            //((JavascriptExecutor) driver).executeScript("document.getElementById('opc-shipping_method').scrollIntoView();");
            Select select = new Select(driver.findElement(By.id("shippingoption")));
            if (pickupStore.equals("dostava")) {
                select.selectByIndex(0);
            }
            else {
                List<WebElement> options = select.getOptions();
                int optionsNum = options.size();
                for (int i=0; i<optionsNum; i++) {
                    WebElement option = options.get(i);
                    if (option.getAttribute("value").contains(pickupStore)) {
                        select.selectByIndex(i);
                        break;
                    }
                }
            }

            driver.findElement(By.className("shipping-method-next-step-button")).click();

            try {
                Thread.sleep(2000);
            } catch (InterruptedException ex) {
                Logger.getLogger(Orderer.class.getName()).log(Level.SEVERE, null, ex);
            }

            driver.findElement(By.className("payment-method-next-step-button")).click();

            try {
                Thread.sleep(2000);  // delay required before performing check-out
            } catch (InterruptedException ex) {
                Logger.getLogger(Orderer.class.getName()).log(Level.SEVERE, null, ex);
            }
            
            String totalCost = driver.findElement(By.cssSelector(".product-price.order-total > strong")).getText();
            
            driver.findElement(By.id("termsofservice")).sendKeys(" "); // hitting space button (un)checks checkbox

            //driver.findElementByClassName("confirm-order-next-step-button").click();  // final step
            
            System.out.println(String.format("Narudžba je uspješno zaprimljena. Iznos od %s ćete platiti pouzećem", totalCost));
            
            driver.close();
        } else {
            System.out.println("Program je pokrenut s nevaljanim brojem parametara");
        }
    }
}