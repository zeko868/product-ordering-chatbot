/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package Orderer;

import java.nio.file.Paths;
import java.util.ArrayList;
import java.util.List;
import java.util.logging.Level;
import java.util.logging.Logger;
import org.openqa.selenium.By;
import org.openqa.selenium.JavascriptExecutor;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import org.openqa.selenium.chrome.ChromeDriver;
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
            //System.setProperty("phantomjs.binary.path", Paths.get(System.getProperty("user.dir"), "phantomjs.exe").toString());
            driver = new PhantomJSDriver();
            
            List<String> productsInfo = new ArrayList<>();
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
                WebElement imageBox = driver.findElement(By.cssSelector("img[id^=main-product-img-]"));
                String productName = imageBox.getAttribute("title");
                String productImageUrl = imageBox.getAttribute("src");
                String price = driver.findElement(By.cssSelector("meta[itemprop=price]")).getAttribute("content");
                productsInfo.add(productName);
                productsInfo.add(productImageUrl);
                productsInfo.add(price);
            }

            if (!pickupStore.isEmpty()) {
                try {
                    Thread.sleep(500);  // delay required before performing check-out
                } catch (InterruptedException ex) {
                    Logger.getLogger(Orderer.class.getName()).log(Level.SEVERE, null, ex);
                }

                if (driver.getClass() != ChromeDriver.class) {
                    ((JavascriptExecutor)driver).executeScript("setLocation('/hr/login/checkoutasguest?returnUrl=%2Fhr%2Fcart');");
                    try {
                        Thread.sleep(3000);
                    } catch (InterruptedException ex) {
                        Logger.getLogger(Orderer.class.getName()).log(Level.SEVERE, null, ex);
                    }

                    driver.findElement(By.className("checkout-as-guest-button")).click();

                    try {
                        Thread.sleep(3000);
                    } catch (InterruptedException ex) {
                        Logger.getLogger(Orderer.class.getName()).log(Level.SEVERE, null, ex);
                    }

                    JavascriptExecutor jsExecutor = ((JavascriptExecutor) driver);
                    jsExecutor.executeScript("document.getElementById('BillingNewAddress_FirstName').value = arguments[0];", firstName);
                    jsExecutor.executeScript("document.getElementById('BillingNewAddress_LastName').value = arguments[0];", lastName);
                    jsExecutor.executeScript("document.getElementById('BillingNewAddress_Email').value = arguments[0];", email);
                    jsExecutor.executeScript("document.getElementById('BillingNewAddress_Address1').value = arguments[0];", address);
                    jsExecutor.executeScript("document.getElementById('BillingNewAddress_ZipPostalCode').value = arguments[0];", zipCode);
                    jsExecutor.executeScript("document.getElementById('BillingNewAddress_City').value = arguments[0];", cityName);
                    jsExecutor.executeScript("document.getElementById('BillingNewAddress_CountryId').value = arguments[0];", countryCode);
                    jsExecutor.executeScript("document.getElementById('BillingNewAddress_PhoneNumber').value = arguments[0];", phoneNum);
                }
                else {
                    driver.get("https://www.links.hr/hr/onepagecheckout");

                    driver.findElement(By.id("BillingNewAddress_FirstName")).sendKeys(firstName);
                    driver.findElement(By.id("BillingNewAddress_LastName")).sendKeys(lastName);
                    driver.findElement(By.id("BillingNewAddress_Email")).sendKeys(email);
                    driver.findElement(By.id("BillingNewAddress_Address1")).sendKeys(address);
                    driver.findElement(By.cssSelector("label[for=\"BillingNewAddress_ZipPostalCode\"] ~ input")).sendKeys(zipCode);
                    driver.findElement(By.id("BillingNewAddress_PhoneNumber")).sendKeys(phoneNum);
                }


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

                if (driver.getClass() == ChromeDriver.class) {
                    ((JavascriptExecutor) driver).executeScript("document.getElementById('shipping-buttons-container').scrollIntoView();");
                }
                driver.findElement(By.cssSelector("#shipping-buttons-container .new-address-next-step-button")).click();

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

                System.out.println(totalCost);
            }
            else {
                System.out.println(""); // empty line so that result in both cases is consistent (in same format)
            }
            
            for (String pi : productsInfo) {
                System.out.println(pi);
            }
            
            driver.quit();
        } else {
            System.out.println("Program je pokrenut s nevaljanim brojem parametara");
        }
    }
}