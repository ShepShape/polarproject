#define FASTLED_ESP8266_NODEMCU_PIN_ORDER
#include <Arduino.h>
#include <FastLED.h>
#include <ESP8266WiFi.h>
#include <ESP8266WiFiMulti.h>
#include <ESP8266HTTPClient.h>
#define DATA_PIN 7 
#define USE_SERIAL Serial

//these can be changed to reflect number of LEDs in the string, how frequently to update, WIFI ssid and WIFI password
#define NUM_LEDS 3
#define UPDATE_SECONDS 10
#define WIFI_SSID "glydemedialab"
#define WIFI_PASS "palliser319"

CRGB leds[NUM_LEDS];
int remote_r,remote_g,remote_b;




ESP8266WiFiMulti WiFiMulti;

void setup() {
    FastLED.addLeds<NEOPIXEL, DATA_PIN>(leds, NUM_LEDS);
    USE_SERIAL.begin(115200);
    for(uint8_t t = 4; t > 0; t--) {
        USE_SERIAL.flush();
        delay(1000);
        USE_SERIAL.println("");
    }
    WiFiMulti.addAP(WIFI_SSID, WIFI_PASS);
    while (WiFiMulti.run() != WL_CONNECTED) {
      delay(1000);
      USE_SERIAL.println("connecting...");
    }
    USE_SERIAL.println("Wifi Connected");
}

void loop() {
    webColorGet();
    setLEDColor();
    delay(UPDATE_SECONDS * 1000);
}

void setLEDColor() {
  for (int i=0;i<=NUM_LEDS; i++) {
   leds[i].red   = remote_r;
   leds[i].green = remote_g;
   leds[i].blue  = remote_b;
  }
  FastLED.show(); 
}

void webColorGet() {
    HTTPClient http;
    http.begin("http://kimsheppard.net/polarproject/albedolight.php");
    int httpCode = http.GET();
    if (httpCode == HTTP_CODE_OK) {
       String result = http.getString();
       int resultLength = result.length() + 1; 
       char tmp[resultLength];
       result.toCharArray(tmp,resultLength);
       char *rgb = strtok(tmp,":");
       int j=0;
       while (rgb != NULL) {
          if (j==0) remote_r = atoi(rgb);
          if (j==1) remote_g = atoi(rgb);
          if (j==2) remote_b = atoi(rgb);
          rgb=strtok(NULL,":");
          j++;
        }
        USE_SERIAL.print("r: ");
        USE_SERIAL.print(remote_r);
        USE_SERIAL.print(" g: ");
        USE_SERIAL.print(remote_g);
        USE_SERIAL.print(" b: ");
        USE_SERIAL.println(remote_b);
    } else {
      USE_SERIAL.printf("HTTP Request Failed With Error Code: %s\n", http.errorToString(httpCode).c_str());
    }
    http.end(); 
}


