file1 = open("workreap-en_US_bak.po", "r", errors="ignore")
file2 = open("workreap-en_US.po", "w")
file3 = open("output.txt", "w")
line = file1.readline()

replaced = 0
skip = 0
txt = ""
count = 0
while line:
    
    file3.write(line)
    if skip:
        skip = 0
        line = file1.readline()
        continue
    txt = line
    if "msgid" in txt and "freelancer" in txt:
        count = count + 1
        txt = txt.replace("freelancer", "gigster")
        replaced = 1
    if "msgid" in txt and "Freelancer" in txt:
        count = count + 1
        txt = txt.replace("Freelancer", "Gigster")
        replaced = 1
    if "msgid" in txt and "Employer" in txt:
        count = count + 1
        txt = txt.replace("Employer", "Guru")
        replaced = 1
    if "msgid" in txt and "employer" in txt:
        count = count + 1
        txt = txt.replace("employer", "guru")
        replaced = 1
    if "msgid" in txt and "Job" in txt:
        count = count + 1          
        txt = txt.replace("Job", "Guru Requirement")
        replaced = 1
    if "msgid" in txt and "job" in txt:
        count = count + 1
        txt = txt.replace("job", "guru requirement")
        replaced = 1
    if "msgid" in line and "service" in line:
        count = count + 1
        txt = txt.replace("service", "gig")
        replaced = 1
    if "msgid" in line and "Service" in line:
        count = count + 1
        txt = txt.replace("Service", "Gig")
        replaced = 1

    if replaced:
         txt = txt.replace("msgid","msgstr")
         replaced = 0
         skip = 1
    if skip:
    #write both THIS line and NEXT line also
      file2.write(line)
      file2.write(txt)
    else:
        file2.write(line)
    line = file1.readline()
print(count)
file1.close()
file2.close()
file3.close()
